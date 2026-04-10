<?php

namespace App\EventSubscriber;

use App\Service\ActivityLogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SecurityEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onInteractiveLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        
        if ($user instanceof \App\Entity\User) {
            $this->activityLogService->log(
                $user,
                'LOGIN',
                'User',
                $user->getId(),
                sprintf('User logged in: %s', $user->getUsername())
            );
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        
        if ($user instanceof \App\Entity\User) {
            $this->activityLogService->log(
                $user,
                'LOGOUT',
                'User',
                $user->getId(),
                sprintf('User logged out: %s', $user->getUsername())
            );
        }
    }
}

