<?php

namespace App\Security\Voter;

use App\Entity\Invoice;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InvoiceVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const SEND = 'send';
    const MANAGE = 'manage';
    const CANCEL = 'cancel';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW, self::EDIT, self::DELETE, 
            self::SEND, self::MANAGE, self::CANCEL
        ]) && $subject instanceof Invoice;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Invoice $invoice */
        $invoice = $subject;

        // Vérifier que l'utilisateur appartient à la même entreprise
        if ($invoice->getCompany() !== $user->getCompany()) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return true; // Tous les utilisateurs de l'entreprise peuvent voir

            case self::EDIT:
                return $invoice->canBeModified() && $this->canManageInvoice($user, $invoice);

            case self::DELETE:
                return $invoice->getStatus() === Invoice::STATUS_DRAFT && $user->hasRole('ROLE_ADMIN');

            case self::SEND:
                return $invoice->canBeSent() && $this->canManageInvoice($user, $invoice);

            case self::MANAGE:
                return $this->canManageInvoice($user, $invoice);

            case self::CANCEL:
                return $invoice->canBeCancelled() && $user->hasRole('ROLE_ADMIN');
        }

        return false;
    }

    private function canManageInvoice(User $user, Invoice $invoice): bool
    {
        // Admin et comptable peuvent tout gérer
        if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_COMPTABLE')) {
            return true;
        }

        // L'utilisateur peut gérer ses propres factures
        return $invoice->getCreatedBy() === $user;
    }
}