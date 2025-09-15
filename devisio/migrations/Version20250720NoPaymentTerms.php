<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250720NoPaymentTerms extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables sans la colonne payment_terms dans invoice';
    }

    public function up(Schema $schema): void
    {
        // Tables en PostgreSQL
        $this->addSql("CREATE TABLE company (
            id SERIAL PRIMARY KEY, 
            name VARCHAR(255) NOT NULL, 
            siret VARCHAR(255) DEFAULT NULL, 
            vat_number VARCHAR(255) DEFAULT NULL, 
            address VARCHAR(255) NOT NULL, 
            postal_code VARCHAR(100) NOT NULL, 
            city VARCHAR(255) NOT NULL, 
            country VARCHAR(255) NOT NULL, 
            phone VARCHAR(20) DEFAULT NULL, 
            email VARCHAR(255) DEFAULT NULL, 
            website VARCHAR(255) DEFAULT NULL, 
            logo VARCHAR(255) DEFAULT NULL, 
            vat_rate NUMERIC(5, 2) NOT NULL, 
            is_active BOOLEAN NOT NULL DEFAULT TRUE, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        
        $this->addSql("CREATE TABLE customer (
            id SERIAL PRIMARY KEY, 
            company_id INTEGER NOT NULL, 
            type VARCHAR(50) NOT NULL, 
            first_name VARCHAR(255) NOT NULL, 
            last_name VARCHAR(255) NOT NULL, 
            company_name VARCHAR(255) DEFAULT NULL, 
            email VARCHAR(255) NOT NULL, 
            phone VARCHAR(20) DEFAULT NULL, 
            address VARCHAR(255) NOT NULL, 
            postal_code VARCHAR(100) NOT NULL, 
            city VARCHAR(255) NOT NULL, 
            country VARCHAR(255) NOT NULL, 
            siret VARCHAR(255) DEFAULT NULL, 
            vat_number VARCHAR(255) DEFAULT NULL, 
            is_active BOOLEAN NOT NULL DEFAULT TRUE, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        
        $this->addSql("CREATE TABLE quote (
            id SERIAL PRIMARY KEY, 
            company_id INTEGER NOT NULL, 
            customer_id INTEGER NOT NULL, 
            created_by_id INTEGER NOT NULL, 
            number VARCHAR(255) NOT NULL UNIQUE, 
            status VARCHAR(50) NOT NULL, 
            subject VARCHAR(255) NOT NULL, 
            description TEXT DEFAULT NULL, 
            quote_date DATE NOT NULL, 
            valid_until DATE NOT NULL, 
            subtotal NUMERIC(10, 2) NOT NULL, 
            vat_amount NUMERIC(10, 2) NOT NULL, 
            total NUMERIC(10, 2) NOT NULL, 
            terms TEXT DEFAULT NULL, 
            notes TEXT DEFAULT NULL, 
            sent_at TIMESTAMP DEFAULT NULL, 
            accepted_at TIMESTAMP DEFAULT NULL, 
            rejected_at TIMESTAMP DEFAULT NULL, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        
        $this->addSql("CREATE TABLE invoice (
            id SERIAL PRIMARY KEY, 
            company_id INTEGER NOT NULL, 
            customer_id INTEGER NOT NULL, 
            created_by_id INTEGER NOT NULL, 
            quote_id INTEGER DEFAULT NULL UNIQUE, 
            number VARCHAR(255) NOT NULL UNIQUE, 
            status VARCHAR(50) NOT NULL, 
            subject VARCHAR(255) NOT NULL, 
            description TEXT DEFAULT NULL, 
            invoice_date DATE NOT NULL, 
            due_date DATE NOT NULL, 
            subtotal NUMERIC(10, 2) NOT NULL, 
            vat_amount NUMERIC(10, 2) NOT NULL, 
            total NUMERIC(10, 2) NOT NULL, 
            notes TEXT DEFAULT NULL, 
            sent_at TIMESTAMP DEFAULT NULL, 
            paid_at TIMESTAMP DEFAULT NULL, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        
        $this->addSql("CREATE TABLE product (
            id SERIAL PRIMARY KEY, 
            company_id INTEGER NOT NULL, 
            name VARCHAR(255) NOT NULL, 
            description VARCHAR(255) DEFAULT NULL, 
            type VARCHAR(50) NOT NULL, 
            price NUMERIC(10, 2) NOT NULL, 
            unit VARCHAR(10) NOT NULL, 
            is_active BOOLEAN NOT NULL DEFAULT TRUE, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        
        $this->addSql("CREATE TABLE season (
            id SERIAL PRIMARY KEY, 
            company_id INTEGER NOT NULL, 
            name VARCHAR(255) NOT NULL, 
            start_date DATE NOT NULL, 
            end_date DATE NOT NULL, 
            multiplier NUMERIC(5, 2) NOT NULL, 
            is_active BOOLEAN NOT NULL DEFAULT TRUE, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        
        $this->addSql("CREATE TABLE product_price (
            id SERIAL PRIMARY KEY, 
            product_id INTEGER NOT NULL, 
            season_id INTEGER NOT NULL, 
            price NUMERIC(10, 2) NOT NULL, 
            is_active BOOLEAN NOT NULL DEFAULT TRUE, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        
        $this->addSql("CREATE TABLE users (
            id SERIAL PRIMARY KEY, 
            company_id INTEGER NOT NULL, 
            email VARCHAR(180) NOT NULL UNIQUE, 
            roles JSON NOT NULL DEFAULT '[]', 
            password VARCHAR(255) NOT NULL, 
            first_name VARCHAR(255) NOT NULL, 
            last_name VARCHAR(255) NOT NULL, 
            is_active BOOLEAN NOT NULL DEFAULT TRUE, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        
        $this->addSql("CREATE TABLE quote_item (
            id SERIAL PRIMARY KEY, 
            quote_id INTEGER NOT NULL, 
            product_id INTEGER DEFAULT NULL, 
            product_name VARCHAR(255) NOT NULL, 
            description VARCHAR(255) DEFAULT NULL, 
            unit_price NUMERIC(10, 2) NOT NULL, 
            quantity NUMERIC(10, 2) NOT NULL, 
            unit VARCHAR(10) NOT NULL, 
            total NUMERIC(10, 2) NOT NULL, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        
        $this->addSql("CREATE TABLE invoice_item (
            id SERIAL PRIMARY KEY, 
            invoice_id INTEGER NOT NULL, 
            product_id INTEGER DEFAULT NULL, 
            product_name VARCHAR(255) NOT NULL, 
            description VARCHAR(255) DEFAULT NULL, 
            unit_price NUMERIC(10, 2) NOT NULL, 
            quantity NUMERIC(10, 2) NOT NULL, 
            unit VARCHAR(10) NOT NULL, 
            total NUMERIC(10, 2) NOT NULL, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        
        $this->addSql("CREATE TABLE invoice_reminder (
            id SERIAL PRIMARY KEY, 
            invoice_id INTEGER NOT NULL, 
            type VARCHAR(50) NOT NULL, 
            subject VARCHAR(255) NOT NULL, 
            message TEXT NOT NULL, 
            sent_at TIMESTAMP DEFAULT NULL, 
            is_sent BOOLEAN NOT NULL DEFAULT FALSE, 
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        // Création des index
        $this->addSql("CREATE INDEX IDX_customer_company_id ON customer(company_id)");
        $this->addSql("CREATE INDEX IDX_quote_company_id ON quote(company_id)");
        $this->addSql("CREATE INDEX IDX_quote_customer_id ON quote(customer_id)");
        $this->addSql("CREATE INDEX IDX_quote_created_by_id ON quote(created_by_id)");
        $this->addSql("CREATE INDEX IDX_invoice_company_id ON invoice(company_id)");
        $this->addSql("CREATE INDEX IDX_invoice_customer_id ON invoice(customer_id)");
        $this->addSql("CREATE INDEX IDX_invoice_created_by_id ON invoice(created_by_id)");
        $this->addSql("CREATE INDEX IDX_product_company_id ON product(company_id)");
        $this->addSql("CREATE INDEX IDX_season_company_id ON season(company_id)");
        $this->addSql("CREATE INDEX IDX_product_price_product_id ON product_price(product_id)");
        $this->addSql("CREATE INDEX IDX_product_price_season_id ON product_price(season_id)");
        $this->addSql("CREATE INDEX IDX_users_company_id ON users(company_id)");
        $this->addSql("CREATE INDEX IDX_quote_item_quote_id ON quote_item(quote_id)");
        $this->addSql("CREATE INDEX IDX_quote_item_product_id ON quote_item(product_id)");
        $this->addSql("CREATE INDEX IDX_invoice_item_invoice_id ON invoice_item(invoice_id)");
        $this->addSql("CREATE INDEX IDX_invoice_item_product_id ON invoice_item(product_id)");
        $this->addSql("CREATE INDEX IDX_invoice_reminder_invoice_id ON invoice_reminder(invoice_id)");

        // Création des clés étrangères
        $this->addSql("ALTER TABLE customer ADD CONSTRAINT FK_customer_company_id FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE quote ADD CONSTRAINT FK_quote_company_id FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE quote ADD CONSTRAINT FK_quote_customer_id FOREIGN KEY (customer_id) REFERENCES customer (id)");
        $this->addSql("ALTER TABLE quote ADD CONSTRAINT FK_quote_created_by_id FOREIGN KEY (created_by_id) REFERENCES users (id)");
        $this->addSql("ALTER TABLE invoice ADD CONSTRAINT FK_invoice_company_id FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE invoice ADD CONSTRAINT FK_invoice_customer_id FOREIGN KEY (customer_id) REFERENCES customer (id)");
        $this->addSql("ALTER TABLE invoice ADD CONSTRAINT FK_invoice_created_by_id FOREIGN KEY (created_by_id) REFERENCES users (id)");
        $this->addSql("ALTER TABLE invoice ADD CONSTRAINT FK_invoice_quote_id FOREIGN KEY (quote_id) REFERENCES quote (id)");
        $this->addSql("ALTER TABLE product ADD CONSTRAINT FK_product_company_id FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE season ADD CONSTRAINT FK_season_company_id FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE product_price ADD CONSTRAINT FK_product_price_product_id FOREIGN KEY (product_id) REFERENCES product (id)");
        $this->addSql("ALTER TABLE product_price ADD CONSTRAINT FK_product_price_season_id FOREIGN KEY (season_id) REFERENCES season (id)");
        $this->addSql("ALTER TABLE users ADD CONSTRAINT FK_users_company_id FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE quote_item ADD CONSTRAINT FK_quote_item_quote_id FOREIGN KEY (quote_id) REFERENCES quote (id)");
        $this->addSql("ALTER TABLE quote_item ADD CONSTRAINT FK_quote_item_product_id FOREIGN KEY (product_id) REFERENCES product (id)");
        $this->addSql("ALTER TABLE invoice_item ADD CONSTRAINT FK_invoice_item_invoice_id FOREIGN KEY (invoice_id) REFERENCES invoice (id)");
        $this->addSql("ALTER TABLE invoice_item ADD CONSTRAINT FK_invoice_item_product_id FOREIGN KEY (product_id) REFERENCES product (id)");
        $this->addSql("ALTER TABLE invoice_reminder ADD CONSTRAINT FK_invoice_reminder_invoice_id FOREIGN KEY (invoice_id) REFERENCES invoice (id)");
    }

    public function down(Schema $schema): void
    {
        // Supprimer les contraintes de clés étrangères
        $this->addSql("ALTER TABLE customer DROP CONSTRAINT IF EXISTS FK_customer_company_id");
        $this->addSql("ALTER TABLE quote DROP CONSTRAINT IF EXISTS FK_quote_company_id");
        $this->addSql("ALTER TABLE quote DROP CONSTRAINT IF EXISTS FK_quote_customer_id");
        $this->addSql("ALTER TABLE quote DROP CONSTRAINT IF EXISTS FK_quote_created_by_id");
        $this->addSql("ALTER TABLE invoice DROP CONSTRAINT IF EXISTS FK_invoice_company_id");
        $this->addSql("ALTER TABLE invoice DROP CONSTRAINT IF EXISTS FK_invoice_customer_id");
        $this->addSql("ALTER TABLE invoice DROP CONSTRAINT IF EXISTS FK_invoice_created_by_id");
        $this->addSql("ALTER TABLE invoice DROP CONSTRAINT IF EXISTS FK_invoice_quote_id");
        $this->addSql("ALTER TABLE product DROP CONSTRAINT IF EXISTS FK_product_company_id");
        $this->addSql("ALTER TABLE season DROP CONSTRAINT IF EXISTS FK_season_company_id");
        $this->addSql("ALTER TABLE product_price DROP CONSTRAINT IF EXISTS FK_product_price_product_id");
        $this->addSql("ALTER TABLE product_price DROP CONSTRAINT IF EXISTS FK_product_price_season_id");
        $this->addSql("ALTER TABLE users DROP CONSTRAINT IF EXISTS FK_users_company_id");
        $this->addSql("ALTER TABLE quote_item DROP CONSTRAINT IF EXISTS FK_quote_item_quote_id");
        $this->addSql("ALTER TABLE quote_item DROP CONSTRAINT IF EXISTS FK_quote_item_product_id");
        $this->addSql("ALTER TABLE invoice_item DROP CONSTRAINT IF EXISTS FK_invoice_item_invoice_id");
        $this->addSql("ALTER TABLE invoice_item DROP CONSTRAINT IF EXISTS FK_invoice_item_product_id");
        $this->addSql("ALTER TABLE invoice_reminder DROP CONSTRAINT IF EXISTS FK_invoice_reminder_invoice_id");

        // Supprimer les tables dans l'ordre inverse
        $this->addSql("DROP TABLE IF EXISTS invoice_reminder");
        $this->addSql("DROP TABLE IF EXISTS invoice_item");
        $this->addSql("DROP TABLE IF EXISTS quote_item");
        $this->addSql("DROP TABLE IF EXISTS product_price");
        $this->addSql("DROP TABLE IF EXISTS season");
        $this->addSql("DROP TABLE IF EXISTS product");
        $this->addSql("DROP TABLE IF EXISTS invoice");
        $this->addSql("DROP TABLE IF EXISTS quote");
        $this->addSql("DROP TABLE IF EXISTS users");
        $this->addSql("DROP TABLE IF EXISTS customer");
        $this->addSql("DROP TABLE IF EXISTS company");
    }
}
