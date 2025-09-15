<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250722AddDiscountCodes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table discount_code et les colonnes discount dans quote et invoice';
    }

    public function up(Schema $schema): void
    {
        // Créer la table discount_code
        $this->addSql('CREATE TABLE discount_code (
            id SERIAL PRIMARY KEY, 
            company_id INTEGER NOT NULL, 
            code VARCHAR(50) NOT NULL, 
            name VARCHAR(255) NOT NULL, 
            description TEXT DEFAULT NULL, 
            type VARCHAR(20) NOT NULL, 
            value NUMERIC(10, 2) NOT NULL, 
            minimum_amount NUMERIC(10, 2) DEFAULT NULL, 
            maximum_discount NUMERIC(10, 2) DEFAULT NULL, 
            valid_from DATE DEFAULT NULL, 
            valid_until DATE DEFAULT NULL, 
            usage_limit INTEGER DEFAULT NULL, 
            usage_count INTEGER NOT NULL DEFAULT 0, 
            is_active BOOLEAN NOT NULL DEFAULT true, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        
        // Créer l'index et la contrainte unique
        $this->addSql('CREATE INDEX IDX_discount_code_company_id ON discount_code(company_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_discount_code_company_code ON discount_code(company_id, code)');

        // Ajouter les colonnes discount à la table quote
        $this->addSql('ALTER TABLE quote 
            ADD COLUMN discount_code_id INTEGER DEFAULT NULL, 
            ADD COLUMN discount_amount NUMERIC(10, 2) DEFAULT NULL');
        
        // Ajouter les colonnes discount à la table invoice
        $this->addSql('ALTER TABLE invoice 
            ADD COLUMN discount_code_id INTEGER DEFAULT NULL, 
            ADD COLUMN discount_amount NUMERIC(10, 2) DEFAULT NULL');

        // Ajouter les index pour les nouvelles colonnes
        $this->addSql('CREATE INDEX IDX_quote_discount_code_id ON quote(discount_code_id)');
        $this->addSql('CREATE INDEX IDX_invoice_discount_code_id ON invoice(discount_code_id)');

        // Ajouter les contraintes de clés étrangères
        $this->addSql('ALTER TABLE discount_code ADD CONSTRAINT FK_discount_code_company_id FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_quote_discount_code_id FOREIGN KEY (discount_code_id) REFERENCES discount_code (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_invoice_discount_code_id FOREIGN KEY (discount_code_id) REFERENCES discount_code (id)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les contraintes de clés étrangères
        $this->addSql('ALTER TABLE quote DROP CONSTRAINT IF EXISTS FK_quote_discount_code_id');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT IF EXISTS FK_invoice_discount_code_id');
        $this->addSql('ALTER TABLE discount_code DROP CONSTRAINT IF EXISTS FK_discount_code_company_id');

        // Supprimer les index
        $this->addSql('DROP INDEX IF EXISTS IDX_quote_discount_code_id');
        $this->addSql('DROP INDEX IF EXISTS IDX_invoice_discount_code_id');
        $this->addSql('DROP INDEX IF EXISTS IDX_discount_code_company_id');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_discount_code_company_code');

        // Supprimer les colonnes discount
        $this->addSql('ALTER TABLE quote DROP COLUMN IF EXISTS discount_code_id, DROP COLUMN IF EXISTS discount_amount');
        $this->addSql('ALTER TABLE invoice DROP COLUMN IF EXISTS discount_code_id, DROP COLUMN IF EXISTS discount_amount');

        // Supprimer la table discount_code
        $this->addSql('DROP TABLE IF EXISTS discount_code');
    }
}