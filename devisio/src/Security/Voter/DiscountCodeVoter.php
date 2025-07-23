<?php
// src/Security/Voter/DiscountCodeVoter.php

namespace App\Security\Voter;

use App\Entity\DiscountCode;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DiscountCodeVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const CREATE = 'create';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Si c'est pour la création, $subject sera null ou une string
        if ($attribute === self::CREATE) {
            return $subject === null || $subject === 'DiscountCode';
        }

        // Pour les autres actions, on vérifie que c'est bien un DiscountCode
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof DiscountCode;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        // L'utilisateur doit être connecté
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($user);
            case self::VIEW:
                return $this->canView($subject, $user);
            case self::EDIT:
                return $this->canEdit($subject, $user);
            case self::DELETE:
                return $this->canDelete($subject, $user);
        }

        return false;
    }

    private function canCreate(User $user): bool
    {
        // Seuls les utilisateurs avec une entreprise peuvent créer des codes
        if (!$user->getCompany()) {
            return false;
        }

        // Vérifier les rôles autorisés
        return $user->hasRole('ROLE_ADMIN') || 
               $user->hasRole('ROLE_MANAGER') || 
               $user->hasRole('ROLE_SALES');
    }

    private function canView(DiscountCode $discountCode, User $user): bool
    {
        // L'utilisateur doit appartenir à la même entreprise
        if (!$user->getCompany() || $user->getCompany() !== $discountCode->getCompany()) {
            return false;
        }

        // Tous les utilisateurs de l'entreprise peuvent voir les codes
        return true;
    }

    private function canEdit(DiscountCode $discountCode, User $user): bool
    {
        // L'utilisateur doit appartenir à la même entreprise
        if (!$user->getCompany() || $user->getCompany() !== $discountCode->getCompany()) {
            return false;
        }

        // Seuls certains rôles peuvent modifier
        return $user->hasRole('ROLE_ADMIN') || 
               $user->hasRole('ROLE_MANAGER') || 
               $user->hasRole('ROLE_SALES');
    }

    private function canDelete(DiscountCode $discountCode, User $user): bool
    {
        // L'utilisateur doit appartenir à la même entreprise
        if (!$user->getCompany() || $user->getCompany() !== $discountCode->getCompany()) {
            return false;
        }

        // Vérifier si le code a été utilisé
        if ($discountCode->getUsageCount() > 0) {
            // Seuls les admins peuvent supprimer un code utilisé
            return $user->hasRole('ROLE_ADMIN');
        }

        // Pour les codes non utilisés, managers et admins peuvent supprimer
        return $user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_MANAGER');
    }
}