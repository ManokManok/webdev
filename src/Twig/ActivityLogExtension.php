<?php

namespace App\Twig;

use App\Repository\ActivityLogRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ActivityLogExtension extends AbstractExtension
{
    public function __construct(
        private ActivityLogRepository $activityLogRepository
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_recent_activities', [$this, 'getRecentActivities']),
        ];
    }

    public function getRecentActivities(int $limit = 5): array
    {
        return $this->activityLogRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}


