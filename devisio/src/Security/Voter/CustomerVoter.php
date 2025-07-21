<?php

namespace App\Security\Voter;

use App\Entity\Customer;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CustomerVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE]) 
            && $subject instanceof Customer;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Customer $customer */
        $customer = $subject;

        // Vérifier que l'utilisateur appartient à la même entreprise
        if ($customer->getCompany() !== $user->getCompany()) {
            return false;
        }

        // NOUVEAUTÉ : Bloquer toutes les opérations sur les clients inactifs
        // sauf la consultation et la réactivation (edit pour réactiver)
        if (!$customer->isActive() && $attribute !== self::VIEW && $attribute !== self::EDIT) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return true; // Tous les utilisateurs peuvent voir (même inactifs)

            case self::EDIT:
                return $this->canEdit($user); // Permet de réactiver un client inactif

            case self::DELETE:
                return $this->canDelete($user, $customer);
        }

        return false;
    }

    private function canEdit(User $user): bool
    {
        // Admin et comptable peuvent modifier
        if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_COMPTABLE')) {
            return true;
        }

        // Les employés peuvent modifier si autorisé
        return $user->hasRole('ROLE_EMPLOYE');
    }

    private function canDelete(User $user, Customer $customer): bool
    {
        // Seuls les admins peuvent supprimer
        if (!$user->hasRole('ROLE_ADMIN')) {
            return false;
        }

        // Ne pas supprimer si le client a des devis ou factures
        return $customer->getQuotes()->count() === 0 && $customer->getInvoices()->count() === 0;
    }
}