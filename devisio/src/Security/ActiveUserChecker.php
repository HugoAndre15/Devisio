<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ActiveUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérifier si l'utilisateur est actif
        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Votre compte a été désactivé. Contactez un administrateur.');
        }

        // Vérifier si l'entreprise est active
        if (!$user->getCompany() || !$user->getCompany()->isActive()) {
            throw new CustomUserMessageAccountStatusException('Votre entreprise a été désactivée. Contactez le support.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérifications post-authentification si nécessaire
        // Par exemple, vérifier si l'utilisateur n'a pas été désactivé pendant sa session
    }
}