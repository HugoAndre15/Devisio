<?php

namespace App\Security\Voter;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProductVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE]) 
            && $subject instanceof Product;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Product $product */
        $product = $subject;

        // Vérifier que l'utilisateur appartient à la même entreprise
        if ($product->getCompany() !== $user->getCompany()) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return true; // Tous les utilisateurs de l'entreprise peuvent voir

            case self::EDIT:
                return $this->canEdit($user);

            case self::DELETE:
                return $this->canDelete($user, $product);
        }

        return false;
    }

    private function canEdit(User $user): bool
    {
        // Admin et comptable peuvent modifier
        if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_COMPTABLE')) {
            return true;
        }

        // Les employés peuvent modifier
        return $user->hasRole('ROLE_EMPLOYE');
    }

    private function canDelete(User $user, Product $product): bool
    {
        // Seuls les admins peuvent supprimer
        if (!$user->hasRole('ROLE_ADMIN')) {
            return false;
        }

        // Ne pas supprimer si le produit est utilisé
        return $product->getQuoteItems()->count() === 0 && $product->getInvoiceItems()->count() === 0;
    }
}