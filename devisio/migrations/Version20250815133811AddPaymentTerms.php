<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250815133811AddPaymentTerms extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des conditions de paiement pour les devis et factures';
    }

    public function up(Schema $schema): void
    {
        // Ajouter la colonne payment_terms à la table invoice
        $this->addSql('ALTER TABLE invoice ADD COLUMN payment_terms VARCHAR(255) DEFAULT NULL');

        // Ajouter l'index pour la nouvelle colonne
        $this->addSql('CREATE INDEX IDX_invoice_payment_terms ON invoice(payment_terms)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer la colonne payment_terms de la table invoice
        $this->addSql('ALTER TABLE invoice DROP COLUMN payment_terms');

        // Supprimer l'index pour la colonne payment_terms
        $this->addSql('DROP INDEX IDX_invoice_payment_terms ON invoice');
    }
}
