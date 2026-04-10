<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity-logs')]
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('', name: 'app_activity_logs')]
    public function index(ActivityLogRepository $activityLogRepository): Response
    {
        // Get all activity logs, ordered by most recent first
        $activityLogs = $activityLogRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('activity_log/index.html.twig', [
            'activityLogs' => $activityLogs,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_log_view')]
    public function view(ActivityLog $activityLog): Response
    {

        return $this->render('activity_log/view.html.twig', [
            'log' => $activityLog,
        ]);
    }
}
