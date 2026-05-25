<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (0 !== strpos($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $status = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

        $message = $exception instanceof HttpExceptionInterface
            ? $exception->getMessage() ?: 'API request failed.'
            : 'An unexpected server error occurred. Please try again later.';

        if (!$exception instanceof HttpExceptionInterface) {
            error_log(sprintf('API error on %s: %s', $request->getPathInfo(), $exception->getMessage()));
        }

        $response = new JsonResponse([
            'status' => 'error',
            'message' => $message,
        ], $status);

        $event->setResponse($response);
    }
}
