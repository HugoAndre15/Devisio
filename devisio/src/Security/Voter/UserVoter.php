<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE]) 
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var User $targetUser */
        $targetUser = $subject;

        // Vérifier que l'utilisateur appartient à la même entreprise
        if ($targetUser->getCompany() !== $user->getCompany()) {
            return false;
        }

        // Seuls les admins peuvent gérer les utilisateurs
        if (!$user->hasRole('ROLE_ADMIN')) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return true; // Les admins peuvent voir tous les utilisateurs de leur entreprise

            case self::EDIT:
                return $this->canEdit($user, $targetUser);

            case self::DELETE:
                return $this->canDelete($user, $targetUser);
        }

        return false;
    }

    private function canEdit(User $user, User $targetUser): bool
    {
        // Un admin peut modifier tout le monde sauf s'il essaie de se retirer ses propres droits admin
        // (cette vérification sera faite côté métier dans le contrôleur)
        return true;
    }

    private function canDelete(User $user, User $targetUser): bool
    {
        // Ne peut pas se supprimer soi-même
        if ($user === $targetUser) {
            return false;
        }

        // Ne peut pas supprimer si l'utilisateur a créé des devis ou factures
        if ($targetUser->getQuotes()->count() > 0 || $targetUser->getInvoices()->count() > 0) {
            return false;
        }

        // Ne peut pas supprimer le dernier admin (cette vérification est déjà dans le contrôleur)
        return true;
    }
}