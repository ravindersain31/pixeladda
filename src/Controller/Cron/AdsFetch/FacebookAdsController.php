<?php

namespace App\Controller\Cron\AdsFetch;

use App\Service\ThirdPartyTokenService;
use App\Entity\Store;
use App\Service\CogsHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FacebookAdsController extends AbstractController
{
    private $api_version = 'v23.0';
    private $appId = null;
    private $appSecret = null;
    private $accessToken = null;
    private $adAccountId = null;
    private $defaultAccessToken = "EAASMcv2su6oBQLjdj1gh6rLmZBiFuFNxFmnMebZBqgtm2wxkSZAMJcmtnvnWWVncjc2EHCRo3Uk2SspcDmGLWwG95tgW5ZAqcZA3Nu8DTPX0fw0Vguu5NJOVAupa8ulnLI352iVLuGthp6T7g14NmeePTahMMt5QLLjXuQcWeCXZCpjTv7jxwVUqHJK9mwRNaM";

    public function __construct(
        private readonly ParameterBagInterface  $params,
        private readonly ThirdPartyTokenService $thirdPartyTokenService,
        private readonly EntityManagerInterface $entityManagerInterface,
        private readonly CogsHandlerService     $cogs,
    ) {
        $this->appId = $this->params->get('FACEBOOK_APP_ID');
        $this->appSecret = $this->params->get('FACEBOOK_APP_SECRET');
        $this->accessToken = $this->params->get('FACEBOOK_ACCESS_TOKEN');
        $this->adAccountId = $this->params->get('FACEBOOK_AD_ACCOUNT_ID');
    }

    #[Route('/fetch-facebook-ads-data', name: 'cron_fetch_facebook_ads_data')]
    public function fetchYesterdayData(Request $request): Response
    {
        $date = new \DateTimeImmutable();
        $date = $date->modify('-1 day');
        $customDate = \DateTimeImmutable::createFromFormat('Y-m-d', $request->get('date', $date->format('Y-m-d')));
        if ($customDate !== false) {
            $date = $customDate;
        }

        return $this->fetchFacebookAdsData($date);
    }

    #[Route('/fetch-facebook-ads-data-today', name: 'cron_fetch_facebook_ads_data_today')]
    public function fetchTodayData(Request $request): Response
    {
        $date = new \DateTimeImmutable();
        return $this->fetchFacebookAdsData($date);
    }

    private function buildFacebookAdsApiUrl(\DateTimeImmutable $date, string $accessToken): string
    {
        $dateRange = [
            'since' => $date->format('Y-m-d'),
            'until' => $date->format('Y-m-d'),
        ];

        $dateRangeQuery = http_build_query(['time_range' => $dateRange]);

        $endpoint = "https://graph.facebook.com/{$this->api_version}/{$this->adAccountId}/insights";
        $fields = 'spend,actions,action_values,clicks,impressions,conversions,date_start,date_stop';

        $url = "{$endpoint}?fields={$fields}&{$dateRangeQuery}&access_token={$accessToken}";

        return $url;
    }

    private function fetchFacebookAdsData(\DateTimeImmutable $date): Response
    {
        try {
            $store = $this->entityManagerInterface->getRepository(Store::class)->findOneBy(['shortName' => 'YSP']);
            $accessToken = $this->ReadOAuthRefreshToken();

            $accountDetails = $this->fetchFacebookAccountDetails($accessToken);
            if (!$accountDetails['AccountId'] || !$accountDetails['AccountName']) {
                throw new \RuntimeException('Failed to fetch Facebook account details');
            }
            $url = $this->buildFacebookAdsApiUrl($date, $accessToken);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) {
                $errorData = json_decode($response, true);
                $errorMessage = $errorData['error']['message'] ?? 'Failed to fetch account details: Unknown error';
                throw new \RuntimeException($errorMessage);
            }

            $data = json_decode($response, true);

            if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                $adData = reset($data['data']);
                $parsedData = [
                    'Spend' => isset($adData['spend']) ? (float)$adData['spend'] : 0,
                    'Clicks' => isset($adData['clicks']) ? (int)$adData['clicks'] : 0,
                    'Impressions' => isset($adData['impressions']) ? (int)$adData['impressions'] : 0,
                    'Conversions' => isset($adData['actions']) ? $this->getActionValue($adData['actions'], 'purchase') : 0,
                    'Revenue' => isset($adData['action_values']) ? $this->getActionValue($adData['action_values'], 'purchase') : 0,
                    'ConversionRate' => isset($adData['clicks'], $adData['actions']) ? $this->calculateConversionRate($adData) : 0,
                    'ReturnOnAdSpend' => isset($adData['spend'], $adData['action_values']) ? $this->calculateROAS($adData) : 0,
                    'TimePeriod' => $adData['date_start'] ?? null,
                    ...$accountDetails,
                ];

                $this->cogs->saveFacebookAdsSpent($date, $store, $parsedData);
                return $this->json([
                    'status' => 'ok',
                    'date' => $date->format('Y-m-d'),
                ]);
            }
            return $this->json([
                'status' => 'failed',
                'date' => $date->format('Y-m-d'),
            ]);
            // throw new \RuntimeException('No data found or API response error');

        } catch (\RuntimeException $runtimeException) {
            return $this->json(['error' => 'Facebook Ads Sync Failure', 'details' => $runtimeException->getMessage()], 400);
        } catch (\Exception $exception) {
            return $this->json(['error' => 'An unexpected error occurred', 'details' => $exception->getMessage()], 500);
        }
    }

    private function getActionValue(array $actions, string $actionType): float
    {
        foreach ($actions as $action) {
            if ($action['action_type'] === $actionType) {
                return (float) $action['value'];
            }
        }
        return 0;
    }

    private function calculateConversionRate(array $adData): float
    {
        $clicks = $adData['clicks'] ?? 0;
        $conversions = $this->getActionValue($adData['actions'] ?? [], 'purchase');
        // Conversion Rate = (Conversions / Clicks) * 100
        return $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0;
    }

    private function calculateROAS(array $adData): float
    {
        $revenue = $this->getActionValue($adData['action_values'] ?? [], 'purchase');
        $spend = $adData['spend'] ?? 0;
        // ROAS (Return on Ad Spend) = Revenue / Ad Spend
        return $spend > 0 ? round($revenue / $spend, 2) : 0;
    }

    private function ReadOAuthRefreshToken(): string
    {
        // Step 1: Check database for a stored token
        $accessToken = $this->thirdPartyTokenService->getFacebookAccessToken();

        if ($accessToken) {
            $isExpired = $this->isTokenExpired($accessToken);
            if (!$isExpired) {
                return $accessToken;
            }
        }

        // Step 2: Check environment variable token if database token is expired
        $envAccessToken = $this->accessToken;

        if ($envAccessToken) {
            $isExpired = $this->isTokenExpired($envAccessToken);
            if (!$isExpired) {
                $expireAt = $this->getTokenExpiryDate($envAccessToken, $envAccessToken);
                $this->thirdPartyTokenService->saveFacebookAccessToken($envAccessToken, $expireAt);
                return $envAccessToken;
            }
        }

        // Step 3: Use default access token
        $expireAt = $this->getTokenExpiryDate($this->defaultAccessToken, $this->defaultAccessToken);
        $this->thirdPartyTokenService->saveFacebookAccessToken($this->defaultAccessToken, $expireAt);
        return $this->defaultAccessToken;
    }

    private function isTokenExpired(string $accessToken): bool
    {
        $url = "https://graph.facebook.com/{$this->api_version}/debug_token?" . http_build_query([
            'input_token' => $accessToken,
            'access_token' => $accessToken
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return true;
        }

        $data = json_decode($response, true);

        if (isset($data['data']['is_valid']) && $data['data']['is_valid'] === true) {
            return false; // Token is valid
        }

        return true;
    }

    public function regenerateAccessToken(string $accessToken): string
    {
        $url = "https://graph.facebook.com/{$this->api_version}/oauth/access_token?" . http_build_query([
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $accessToken,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Failed to fetch account details: Unknown error';
            throw new \RuntimeException($errorMessage);
        }

        $data = json_decode($response);

        if (isset($data->access_token)) {
            $newAccessToken = $data->access_token;
            $expiresAt = $this->getTokenExpiryDate($accessToken, $newAccessToken);
            $this->thirdPartyTokenService->saveFacebookAccessToken($newAccessToken, $expiresAt);
        }
        return $this->thirdPartyTokenService->getFacebookAccessToken();
    }

    private function getTokenExpiryDate(string $accessToken, string $newAccessToken)
    {
        $expiresAt = "";

        $url = 'https://graph.facebook.com/debug_token?' . http_build_query([
            'input_token' => $accessToken,
            'access_token' => $newAccessToken
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Failed to fetch account details: Unknown error';
            throw new \RuntimeException($errorMessage);
        }

        $data = json_decode($response, true);

        if (isset($data['data'])) {
            $expiresAt = isset($data['data']['expires_at']) ? $data['data']['expires_at'] : null;
        }

        return $expiresAt;
    }

    private function fetchFacebookAccountDetails(string $accessToken): array
    {
        $url = "https://graph.facebook.com/{$this->api_version}/me/adaccounts?fields=name,account_id&access_token={$accessToken}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Failed to fetch account details: Unknown error';
            throw new \RuntimeException($errorMessage);
        }

        $data = json_decode($response, true);

        if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
            $accountDetails = $this->getAccountDetailsById($data['data'], $this->adAccountId);
            if (!empty($accountDetails)) {
                return [
                    'AccountId' => $accountDetails['AccountId'],
                    'AccountName' => $accountDetails['AccountName'],
                ];
            }
        }

        return ['AccountId' => null, 'AccountName' => null];
    }

    function getAccountDetailsById(array $data, string $accountId): ?array
    {
        $filteredAccounts = array_values(array_filter($data, function ($account) use ($accountId) {
            return $account['id'] === $accountId;
        }));

        if (!empty($filteredAccounts)) {
            $account = reset($filteredAccounts);
            return [
                'AccountId' => $account['account_id'],
                'AccountName' => $account['name'],
            ];
        }

        return null;
    }
}
