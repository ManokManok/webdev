<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function log(
        ?User $user,
        string $action,
        string $entity,
        ?int $entityId = null,
        ?string $details = null
    ): void {
        $log = new ActivityLog();
        $log->setUser($user);
        $log->setAction($action);
        $log->setEntity($entity);
        $log->setEntityId($entityId);
        $log->setDetails($details);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}

