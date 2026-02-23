<?php

namespace App\EventListener;

use App\Constant\NotFoundRouteMapping;
use App\Service\SlackManager;
use App\SlackSchema\ErrorLogSchema;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    public function __construct(private readonly SlackManager $slackManager)
    {
    }

    public function onKernelException(ExceptionEvent $event): ?ExceptionEvent
    {
        $exception = $event->getThrowable();
        if ($exception instanceof NotFoundHttpException) {
            $pathname = $event->getRequest()->getPathInfo();
            $queryString = $event->getRequest()->getQueryString();
            if($queryString) {
                $pathname .= '?' . $queryString;
            }
            if(isset(NotFoundRouteMapping::URL_MAPPING[$pathname])) {
                $event->setResponse(new RedirectResponse(NotFoundRouteMapping::URL_MAPPING[$pathname], 301));
            }
            foreach (NotFoundRouteMapping::CATEGORY_MAPPING as $key => $value) {
                if(str_contains($pathname, $key)) {
                    $event->setResponse(new RedirectResponse(str_replace($key, $value, $pathname), 301));
                }
            }
            return null;
        }

        $badRequestMessages = ['Invalid CSRF token.'];
        if ($exception instanceof BadRequestHttpException && in_array($exception->getMessage(), $badRequestMessages)) {
            return null;
        }

        $message = $this->prepareMessage($event);

        $this->slackManager->send(SlackManager::ERROR_LOG, ErrorLogSchema::get($message));
        return $event;
    }

    private function prepareMessage(ExceptionEvent $event): string
    {
        $exception = $event->getThrowable();

        $message = sprintf(
            'An error "%s" with code "%s"',
            $exception->getMessage(),
            $exception->getCode()
        );

        $message .= sprintf(
            ' has been found in file "%s" at the line no "%s"',
            $exception->getFile(),
            $exception->getLine(),
        );

        $message .= ' (' . get_class($exception) . ')';
        return $message;
    }
}