<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409081557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE destination (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, pays VARCHAR(100) NOT NULL, code_iata VARCHAR(3) DEFAULT NULL, description LONGTEXT DEFAULT NULL, image_url VARCHAR(500) DEFAULT NULL, video_url VARCHAR(500) DEFAULT NULL, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, `order` INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE voyages (id INT AUTO_INCREMENT NOT NULL, destination VARCHAR(255) NOT NULL, dateDebut DATE NOT NULL, dateFin DATE NOT NULL, prix DOUBLE PRECISION NOT NULL, imagePath VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, pays_depart VARCHAR(100) DEFAULT NULL, destination_id INT DEFAULT NULL, INDEX IDX_30F7F9816C6140 (destination_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE voyages ADD CONSTRAINT FK_30F7F9816C6140 FOREIGN KEY (destination_id) REFERENCES destination (id)');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE voyages DROP FOREIGN KEY FK_30F7F9816C6140');
        $this->addSql('DROP TABLE destination');
        $this->addSql('DROP TABLE voyages');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
    }
}
