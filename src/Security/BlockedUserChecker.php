<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Called by Symfony BEFORE password verification.
 * Blocks login for suspended accounts with a clean inline message.
 */
class BlockedUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isBlocked()) {
            throw new CustomUserMessageAccountStatusException(
                'blocked:Your account has been suspended. Please contact admin@smarttrip.com'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Nothing needed post-auth
    }
}
