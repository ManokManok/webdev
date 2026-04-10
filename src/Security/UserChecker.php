<?php

namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;


class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        // This prevents unverified users from probing passwords.
        if (!$user->IsVerified()) {
            throw new CustomUserMessageAccountStatusException('Your account is not verified. Please check your email inbox.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // if (!$user instanceof AppUser) {
        //     return;
        // }

        // // --- THE LOGIC GOES HERE ---

        // $status = $user->getStatus()->value; // e.g., 'Deactivated', 'Pending', 'Active'

        // if ($status === 'Deactivated') {
        //     // This message will be shown to the user on the login page
        //     throw new CustomUserMessageAccountStatusException('Your account has been deactivated. Please contact the administrator.');
        // }

        // if ($status === 'Pending') {
        //     throw new CustomUserMessageAccountStatusException('Your account is pending approval.');
        // }
    }
}