<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RecaptchaManager
{
    public function shouldShowRecaptchaV2(
        Request $request,
        RateLimiterFactory $limiterFactory,
        ?string $limiterKey = null,
        int $durationSeconds = 7200
    ): bool {
        $ip = getHostByName(getHostName());

        $session = $request->getSession();
        $limiter = $limiterFactory->create($limiterKey ?? $ip);

        // Read-only check
        $limit = $limiter->consume(0);

        $recaptchaEnabledAt = $session->get('recaptcha_enabled_at');
        $now = time();

        
        if ($recaptchaEnabledAt && ($now - $recaptchaEnabledAt) < $durationSeconds) {
            return true;
        }

        if (!$limit->isAccepted() || $limit->getRemainingTokens() === 0) {
            if (!$recaptchaEnabledAt) {
                $session->set('recaptcha_enabled_at', $now);
            }
            return true;
        }

        return false;
    }


     public function shouldShowRecaptcha(Request $request, RateLimiterFactory $limiterFactory, ?string $limiterKey = null, int $durationSeconds = 7200): bool
    {
        $ip = getHostByName(getHostName());
        $session = $request->getSession();
        $limiter = $limiterFactory->create($limiterKey ?? $ip);
        $limit = $limiter->consume(0);
        $recaptchaEnabledAt = $session->get('recaptcha_enabled_at');
        $now = time();
        if ($recaptchaEnabledAt &&  $limit->getRemainingTokens() === 0) {
            return false;
        }

        if ($recaptchaEnabledAt && ($now - $recaptchaEnabledAt) < $durationSeconds) {
            return true;
        }

        if (!$limit->isAccepted() || ($limit->isAccepted() && $limit->getRemainingTokens() === 0)) {
            if (!$session->has('recaptcha_enabled_at')) {
                $session->set('recaptcha_enabled_at', $now);
            }
            return true;
        }

        if ($recaptchaEnabledAt) {
            $session->remove('recaptcha_enabled_at');
        }

        return false;
    }
}
