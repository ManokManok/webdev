<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\ChangePasswordType;
use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ActivityLogService $activityLogService
    ) {
    }

    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $filter = $request->query->get('filter', 'all');
        
        $users = match($filter) {
            'active' => $userRepository->findBy(['isActive' => true, 'isArchived' => false]),
            'inactive' => $userRepository->findBy(['isActive' => false, 'isArchived' => false]),
            'archived' => $userRepository->findBy(['isArchived' => true]),
            default => $userRepository->findBy(['isArchived' => false], ['createdAt' => 'DESC']),
        };

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'filter' => $filter,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Convert single role to array
            $selectedRole = $form->get('roles')->getData();
            if ($selectedRole) {
                $user->setRoles([$selectedRole]);
            } else {
                // Default to ROLE_STAFF if no role selected
                $user->setRoles(['ROLE_STAFF']);
            }

            // Hash password
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $user,
                    $plainPassword
                );
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'CREATE',
                'User',
                $user->getId(),
                sprintf('Created user: %s (%s)', $user->getUsername(), $user->getEmail())
            );

            $this->addFlash('success', 'User created successfully.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user, ['edit_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Convert single role to array
            $selectedRole = $form->get('roles')->getData();
            if ($selectedRole) {
                $user->setRoles([$selectedRole]);
            }

            // Update password if provided
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $user,
                    $plainPassword
                );
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'UPDATE',
                'User',
                $user->getId(),
                sprintf('Updated user: %s (%s)', $user->getUsername(), $user->getEmail())
            );

            $this->addFlash('success', 'User updated successfully.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, ActivityLogRepository $activityLogRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $username = $user->getUsername();
            $userId = $user->getId();

            // Set user to null for all activity logs referencing this user
            // This prevents foreign key constraint violations
            $activityLogs = $activityLogRepository->findBy(['user' => $user]);
            foreach ($activityLogs as $log) {
                $log->setUser(null);
            }
            $this->entityManager->flush();

            // Now safe to delete the user
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'DELETE',
                'User',
                $userId,
                sprintf('Deleted user: %s', $username)
            );

            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $this->entityManager->flush();

            $status = $user->isActive() ? 'enabled' : 'disabled';
            $this->activityLogService->log(
                $this->getUser(),
                'UPDATE',
                'User',
                $user->getId(),
                sprintf('%s user: %s', ucfirst($status), $user->getUsername())
            );

            $this->addFlash('success', sprintf('User %s successfully.', $status));
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reset-password', name: 'app_user_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, User $user): Response
    {
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);
            $this->entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'UPDATE',
                'User',
                $user->getId(),
                sprintf('Reset password for user: %s', $user->getUsername())
            );

            $this->addFlash('success', 'Password reset successfully.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/reset_password.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/archive', name: 'app_user_archive', methods: ['POST'])]
    public function archive(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('archive'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsArchived(true);
            $this->entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'UPDATE',
                'User',
                $user->getId(),
                sprintf('Archived user: %s', $user->getUsername())
            );

            $this->addFlash('success', 'User archived successfully.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/unarchive', name: 'app_user_unarchive', methods: ['POST'])]
    public function unarchive(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('unarchive'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsArchived(false);
            $this->entityManager->flush();

            $this->activityLogService->log(
                $this->getUser(),
                'UPDATE',
                'User',
                $user->getId(),
                sprintf('Unarchived user: %s', $user->getUsername())
            );

            $this->addFlash('success', 'User unarchived successfully.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}

