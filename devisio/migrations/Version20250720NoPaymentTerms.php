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
        $this->addSql("CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, siret VARCHAR(255) DEFAULT NULL, vat_number VARCHAR(255) DEFAULT NULL, address VARCHAR(255) NOT NULL, postal_code VARCHAR(100) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, vat_rate NUMERIC(5, 2) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, type VARCHAR(50) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, company_name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, address VARCHAR(255) NOT NULL, postal_code VARCHAR(100) NOT NULL, city VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, siret VARCHAR(255) DEFAULT NULL, vat_number VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_81398E09979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, customer_id INT NOT NULL, created_by_id INT NOT NULL, quote_id INT DEFAULT NULL, number VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, subject VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, invoice_date DATE NOT NULL, due_date DATE NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, vat_amount NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, notes LONGTEXT DEFAULT NULL, sent_at VARCHAR(255) DEFAULT NULL, paid_at VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_9065174496901F54 (number), INDEX IDX_90651744979B1AD6 (company_id), INDEX IDX_906517449395C3F3 (customer_id), INDEX IDX_90651744B03A8386 (created_by_id), UNIQUE INDEX UNIQ_90651744DB805178 (quote_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE invoice_item (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, product_id INT DEFAULT NULL, product_name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, unit_price NUMERIC(10, 2) NOT NULL, quantity NUMERIC(10, 2) NOT NULL, unit VARCHAR(10) NOT NULL, total NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_1DDE477B2989F1FD (invoice_id), INDEX IDX_1DDE477B4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE invoice_reminder (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, type VARCHAR(50) NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, sent_at VARCHAR(255) DEFAULT NULL, is_sent TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_5F1F15182989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, type VARCHAR(50) NOT NULL, price NUMERIC(10, 2) NOT NULL, unit VARCHAR(10) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_D34A04AD979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE product_price (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, season_id INT NOT NULL, price NUMERIC(10, 2) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6B9459854584665A (product_id), INDEX IDX_6B9459854EC001D1 (season_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE quote (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, customer_id INT NOT NULL, created_by_id INT NOT NULL, number VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, subject VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, quote_date DATE NOT NULL, valid_until DATE NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, vat_amount NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, terms LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, sent_at VARCHAR(255) DEFAULT NULL, accepted_at VARCHAR(255) DEFAULT NULL, rejected_at VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_6B71CBF496901F54 (number), INDEX IDX_6B71CBF4979B1AD6 (company_id), INDEX IDX_6B71CBF49395C3F3 (customer_id), INDEX IDX_6B71CBF4B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE quote_item (id INT AUTO_INCREMENT NOT NULL, quote_id INT NOT NULL, product_id INT DEFAULT NULL, product_name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, unit_price NUMERIC(10, 2) NOT NULL, quantity NUMERIC(10, 2) NOT NULL, unit VARCHAR(10) NOT NULL, total NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_8DFC7A94DB805178 (quote_id), INDEX IDX_8DFC7A944584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE season (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, name VARCHAR(255) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, multiplier NUMERIC(5, 2) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_F0E45BA9979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D649979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("ALTER TABLE customer ADD CONSTRAINT FK_81398E09979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE invoice ADD CONSTRAINT FK_90651744979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE invoice ADD CONSTRAINT FK_906517449395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)");
        $this->addSql("ALTER TABLE invoice ADD CONSTRAINT FK_90651744B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)");
        $this->addSql("ALTER TABLE invoice ADD CONSTRAINT FK_90651744DB805178 FOREIGN KEY (quote_id) REFERENCES quote (id)");
        $this->addSql("ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477B2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)");
        $this->addSql("ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477B4584665A FOREIGN KEY (product_id) REFERENCES product (id)");
        $this->addSql("ALTER TABLE invoice_reminder ADD CONSTRAINT FK_5F1F15182989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)");
        $this->addSql("ALTER TABLE product ADD CONSTRAINT FK_D34A04AD979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE product_price ADD CONSTRAINT FK_6B9459854584665A FOREIGN KEY (product_id) REFERENCES product (id)");
        $this->addSql("ALTER TABLE product_price ADD CONSTRAINT FK_6B9459854EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)");
        $this->addSql("ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF4979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF49395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)");
        $this->addSql("ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF4B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)");
        $this->addSql("ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A94DB805178 FOREIGN KEY (quote_id) REFERENCES quote (id)");
        $this->addSql("ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A944584665A FOREIGN KEY (product_id) REFERENCES product (id)");
        $this->addSql("ALTER TABLE season ADD CONSTRAINT FK_F0E45BA9979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)");
        $this->addSql("ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09979B1AD6');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744979B1AD6');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517449395C3F3');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744B03A8386');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744DB805178');
        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_1DDE477B2989F1FD');
        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_1DDE477B4584665A');
        $this->addSql('ALTER TABLE invoice_reminder DROP FOREIGN KEY FK_5F1F15182989F1FD');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD979B1AD6');
        $this->addSql('ALTER TABLE product_price DROP FOREIGN KEY FK_6B9459854584665A');
        $this->addSql('ALTER TABLE product_price DROP FOREIGN KEY FK_6B9459854EC001D1');
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY FK_6B71CBF4979B1AD6');
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY FK_6B71CBF49395C3F3');
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY FK_6B71CBF4B03A8386');
        $this->addSql('ALTER TABLE quote_item DROP FOREIGN KEY FK_8DFC7A94DB805178');
        $this->addSql('ALTER TABLE quote_item DROP FOREIGN KEY FK_8DFC7A944584665A');
        $this->addSql('ALTER TABLE season DROP FOREIGN KEY FK_F0E45BA9979B1AD6');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649979B1AD6');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_item');
        $this->addSql('DROP TABLE invoice_reminder');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_price');
        $this->addSql('DROP TABLE quote');
        $this->addSql('DROP TABLE quote_item');
        $this->addSql('DROP TABLE season');
        $this->addSql('DROP TABLE `user`');
    }
}
