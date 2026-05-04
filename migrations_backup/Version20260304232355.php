<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304232355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('DROP INDEX destination_id ON destination');
        $this->addSql('ALTER TABLE destination ADD nom VARCHAR(100) NOT NULL, ADD pays VARCHAR(100) NOT NULL, ADD code_iata VARCHAR(3) DEFAULT NULL, ADD image_url VARCHAR(500) DEFAULT NULL, ADD video_url VARCHAR(500) DEFAULT NULL, ADD date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP destination, DROP dateDebut, DROP dateFin, DROP prix, DROP imagePath, DROP pays_depart, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE destination_id `order` INT DEFAULT NULL');
        $this->addSql('ALTER TABLE voyages ADD destination VARCHAR(255) NOT NULL, ADD dateDebut DATE NOT NULL, ADD dateFin DATE NOT NULL, ADD prix DOUBLE PRECISION NOT NULL, ADD imagePath VARCHAR(255) DEFAULT NULL, ADD pavs_depart VARCHAR(100) DEFAULT NULL, DROP nom, DROP pays, DROP code_iata, DROP image_url, DROP video_url, DROP date_creation, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE `order` destination_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE voyages ADD CONSTRAINT FK_30F7F9816C6140 FOREIGN KEY (destination_id) REFERENCES destination (id)');
        $this->addSql('CREATE INDEX IDX_30F7F9816C6140 ON voyages (destination_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE destination ADD destination VARCHAR(255) NOT NULL, ADD dateDebut DATE NOT NULL, ADD dateFin DATE NOT NULL, ADD prix DOUBLE PRECISION NOT NULL, ADD imagePath VARCHAR(255) DEFAULT \'NULL\', ADD pays_depart VARCHAR(100) DEFAULT \'NULL\', DROP nom, DROP pays, DROP code_iata, DROP image_url, DROP video_url, DROP date_creation, CHANGE description description TEXT DEFAULT NULL, CHANGE `order` destination_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX destination_id ON destination (destination_id)');
        $this->addSql('ALTER TABLE voyages DROP FOREIGN KEY FK_30F7F9816C6140');
        $this->addSql('DROP INDEX IDX_30F7F9816C6140 ON voyages');
        $this->addSql('ALTER TABLE voyages ADD nom VARCHAR(100) NOT NULL, ADD pays VARCHAR(100) NOT NULL, ADD code_iata VARCHAR(3) DEFAULT \'NULL\', ADD image_url VARCHAR(500) DEFAULT \'NULL\', ADD video_url VARCHAR(500) DEFAULT \'NULL\', ADD date_creation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, DROP destination, DROP dateDebut, DROP dateFin, DROP prix, DROP imagePath, DROP pavs_depart, CHANGE description description TEXT DEFAULT NULL, CHANGE destination_id `order` INT DEFAULT NULL');
    }
}
