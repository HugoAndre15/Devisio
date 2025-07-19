<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250719TestData extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des clients et produits de test pour les devis';
    }

    public function up(Schema $schema): void
    {
        // Ajout de clients de test (en supposant que company_id = 1 existe)
        $this->addSql("INSERT INTO customer (company_id, type, first_name, last_name, company_name, email, phone, address, postal_code, city, country, is_active, created_at, updated_at) VALUES
            (1, 'particulier', 'Jean', 'Dupont', NULL, 'jean.dupont@example.com', '0600000001', '1 rue de Paris', '75001', 'Paris', 'France', 1, NOW(), NOW()),
            (1, 'entreprise', 'Marie', 'Durand', 'Durand SARL', 'contact@durand.fr', '0600000002', '2 avenue de Lyon', '69000', 'Lyon', 'France', 1, NOW(), NOW())
        ");

        // Ajout de produits de test (en supposant que company_id = 1 existe)
        $this->addSql("INSERT INTO product (company_id, name, description, type, price, unit, is_active, created_at, updated_at) VALUES
            (1, 'Site web vitrine', 'Création d\'un site vitrine', 'service', 1200.00, 'forfait', 1, NOW(), NOW()),
            (1, 'Maintenance annuelle', 'Maintenance et support', 'service', 300.00, 'an', 1, NOW(), NOW()),
            (1, 'Logo professionnel', 'Création de logo', 'service', 400.00, 'forfait', 1, NOW(), NOW())
        ");
    }

    public function down(Schema $schema): void
    {
        // Suppression des données de test
        $this->addSql("DELETE FROM product WHERE name IN ('Site web vitrine', 'Maintenance annuelle', 'Logo professionnel')");
        $this->addSql("DELETE FROM customer WHERE email IN ('jean.dupont@example.com', 'contact@durand.fr')");
    }
}
