<?php

namespace App\MessageHandler;

use App\Message\SendPushNotification;
use App\Repository\UserRepository;
use App\Service\FcmNotifier;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendPushNotificationHandler
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly FcmNotifier $fcm,
    ) {
    }

    public function __invoke(SendPushNotification $message): void
    {
        $user = $this->users->find($message->userId);
        if ($user === null) {
            return;
        }

        $this->fcm->sendToUser($user, $message->title, $message->body, $message->data);
    }
}
