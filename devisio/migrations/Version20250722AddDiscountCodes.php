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
            id INT AUTO_INCREMENT NOT NULL, 
            company_id INT NOT NULL, 
            code VARCHAR(50) NOT NULL, 
            name VARCHAR(255) NOT NULL, 
            description LONGTEXT DEFAULT NULL, 
            type VARCHAR(20) NOT NULL, 
            value NUMERIC(10, 2) NOT NULL, 
            minimum_amount NUMERIC(10, 2) DEFAULT NULL, 
            maximum_discount NUMERIC(10, 2) DEFAULT NULL, 
            valid_from DATE DEFAULT NULL, 
            valid_until DATE DEFAULT NULL, 
            usage_limit INT DEFAULT NULL, 
            usage_count INT NOT NULL DEFAULT 0, 
            is_active TINYINT(1) NOT NULL DEFAULT 1, 
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            INDEX IDX_4A3F4045979B1AD6 (company_id), 
            UNIQUE INDEX UNIQ_4A3F4045979B1AD677153098 (company_id, code), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Ajouter les colonnes discount à la table quote
        $this->addSql('ALTER TABLE quote 
            ADD discount_code_id INT DEFAULT NULL, 
            ADD discount_amount NUMERIC(10, 2) DEFAULT NULL');
        
        // Ajouter les colonnes discount à la table invoice
        $this->addSql('ALTER TABLE invoice 
            ADD discount_code_id INT DEFAULT NULL, 
            ADD discount_amount NUMERIC(10, 2) DEFAULT NULL');

        // Ajouter les contraintes de clés étrangères
        $this->addSql('ALTER TABLE discount_code 
            ADD CONSTRAINT FK_4A3F4045979B1AD6 
            FOREIGN KEY (company_id) REFERENCES company (id)');
        
        $this->addSql('ALTER TABLE quote 
            ADD CONSTRAINT FK_6B71CBF4E9D8D4CD 
            FOREIGN KEY (discount_code_id) REFERENCES discount_code (id)');
        
        $this->addSql('ALTER TABLE invoice 
            ADD CONSTRAINT FK_90651744E9D8D4CD 
            FOREIGN KEY (discount_code_id) REFERENCES discount_code (id)');

        // Ajouter les index
        $this->addSql('CREATE INDEX IDX_6B71CBF4E9D8D4CD ON quote (discount_code_id)');
        $this->addSql('CREATE INDEX IDX_90651744E9D8D4CD ON invoice (discount_code_id)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les contraintes de clés étrangères
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY FK_6B71CBF4E9D8D4CD');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744E9D8D4CD');
        $this->addSql('ALTER TABLE discount_code DROP FOREIGN KEY FK_4A3F4045979B1AD6');

        // Supprimer les index
        $this->addSql('DROP INDEX IDX_6B71CBF4E9D8D4CD ON quote');
        $this->addSql('DROP INDEX IDX_90651744E9D8D4CD ON invoice');

        // Supprimer les colonnes discount
        $this->addSql('ALTER TABLE quote DROP discount_code_id, DROP discount_amount');
        $this->addSql('ALTER TABLE invoice DROP discount_code_id, DROP discount_amount');

        // Supprimer la table discount_code
        $this->addSql('DROP TABLE discount_code');
    }
}