<?php

namespace App\Security\Voter;

use App\Entity\Quote;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class QuoteVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const SEND = 'send';
    const ACCEPT = 'accept';
    const REJECT = 'reject';
    const CONVERT = 'convert';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW, self::EDIT, self::DELETE, 
            self::SEND, self::ACCEPT, self::REJECT, self::CONVERT
        ]) && $subject instanceof Quote;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Quote $quote */
        $quote = $subject;

        // Vérifier que l'utilisateur appartient à la même entreprise
        if ($quote->getCompany() !== $user->getCompany()) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return true; // Tous les utilisateurs de l'entreprise peuvent voir

            case self::EDIT:
                return $quote->canBeModified() && $this->canManageQuote($user, $quote);

            case self::DELETE:
                return $quote->getStatus() === Quote::STATUS_DRAFT && $this->canManageQuote($user, $quote);

            case self::SEND:
                return $quote->canBeSent() && $this->canManageQuote($user, $quote);

            case self::ACCEPT:
                return $quote->canBeAccepted() && $this->canManageQuote($user, $quote);

            case self::REJECT:
                return $quote->canBeRejected() && $this->canManageQuote($user, $quote);

            case self::CONVERT:
                return $quote->canBeConvertedToInvoice() && $this->canManageQuote($user, $quote);
        }

        return false;
    }

    private function canManageQuote(User $user, Quote $quote): bool
    {
        // Admin et comptable peuvent tout gérer
        if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_COMPTABLE')) {
            return true;
        }

        // L'utilisateur peut gérer ses propres devis
        return $quote->getCreatedBy() === $user;
    }
}