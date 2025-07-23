<?php
// src/Security/Voter/SeasonVoter.php

namespace App\Security\Voter;

use App\Entity\Season;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SeasonVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const CREATE = 'create';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Si c'est pour la création, $subject sera null ou une string
        if ($attribute === self::CREATE) {
            return $subject === null || $subject === 'Season';
        }

        // Pour les autres actions, on vérifie que c'est bien une Season
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Season;
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
        // Seuls les utilisateurs avec une entreprise peuvent créer des saisons
        if (!$user->getCompany()) {
            return false;
        }

        // Vérifier les rôles autorisés
        return $user->hasRole('ROLE_ADMIN') || 
               $user->hasRole('ROLE_MANAGER');
    }

    private function canView(Season $season, User $user): bool
    {
        // L'utilisateur doit appartenir à la même entreprise
        if (!$user->getCompany() || $user->getCompany() !== $season->getCompany()) {
            return false;
        }

        // Tous les utilisateurs de l'entreprise peuvent voir les saisons
        return true;
    }

    private function canEdit(Season $season, User $user): bool
    {
        // L'utilisateur doit appartenir à la même entreprise
        if (!$user->getCompany() || $user->getCompany() !== $season->getCompany()) {
            return false;
        }

        // Vérifier si la saison est en cours d'utilisation
        $now = new \DateTimeImmutable();
        $isCurrentSeason = $season->getStartDate() <= $now && 
                          $season->getEndDate() >= $now && 
                          $season->isActive();

        if ($isCurrentSeason) {
            // Une saison en cours ne peut être modifiée que par les admins
            return $user->hasRole('ROLE_ADMIN');
        }

        // Pour les autres saisons, managers et admins peuvent modifier
        return $user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_MANAGER');
    }

    private function canDelete(Season $season, User $user): bool
    {
        // L'utilisateur doit appartenir à la même entreprise
        if (!$user->getCompany() || $user->getCompany() !== $season->getCompany()) {
            return false;
        }

        // Vérifier si la saison a été utilisée dans des devis/factures
        // Si vous avez une relation avec Quote/Invoice, ajoutez cette vérification :
        // if ($season->hasBeenUsed()) {
        //     return $user->hasRole('ROLE_ADMIN');
        // }

        // Vérifier si c'est une saison en cours
        $now = new \DateTimeImmutable();
        $isCurrentSeason = $season->getStartDate() <= $now && 
                          $season->getEndDate() >= $now && 
                          $season->isActive();

        if ($isCurrentSeason) {
            // Une saison en cours ne peut être supprimée que par les super admins
            return $user->hasRole('ROLE_SUPER_ADMIN');
        }

        // Pour les autres saisons, seuls les admins peuvent supprimer
        return $user->hasRole('ROLE_ADMIN');
    }
}