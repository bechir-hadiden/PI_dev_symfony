<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416083535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE signalement (id INT AUTO_INCREMENT NOT NULL, motif VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, statut VARCHAR(50) DEFAULT \'en_attente\' NOT NULL, created_at DATETIME NOT NULL, ip_address VARCHAR(255) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, email_signaleur VARCHAR(255) DEFAULT NULL, avis_id INT NOT NULL, INDEX IDX_F4B55114197E709F (avis_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114197E709F FOREIGN KEY (avis_id) REFERENCES avis (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_avis_status ON avis');
        $this->addSql('ALTER TABLE avis CHANGE sentiment_analysis sentiment_analysis JSON DEFAULT NULL, CHANGE keywords keywords JSON DEFAULT NULL, CHANGE sentiment_score sentiment_score DOUBLE PRECISION DEFAULT NULL, CHANGE predictive_analysis predictive_analysis JSON DEFAULT NULL, CHANGE satisfaction_score satisfaction_score DOUBLE PRECISION DEFAULT NULL, CHANGE commentaires commentaires JSON DEFAULT NULL, CHANGE destination destination VARCHAR(255) DEFAULT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT NULL, CHANGE longitude longitude DOUBLE PRECISION DEFAULT NULL, CHANGE weather_data weather_data JSON DEFAULT NULL, CHANGE photos photos JSON DEFAULT NULL, CHANGE main_photo main_photo VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'pending\' NOT NULL');
        $this->addSql('DROP INDEX idx_reservation_status ON reservation');
        $this->addSql('ALTER TABLE reservation CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE number_of_passengers number_of_passengers INT NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'pending\' NOT NULL, CHANGE seat_number seat_number VARCHAR(10) DEFAULT NULL, CHANGE boarding_pass_file boarding_pass_file VARCHAR(20) DEFAULT NULL, CHANGE boarding_pass_sent boarding_pass_sent TINYINT NOT NULL, CHANGE admin_notes admin_notes LONGTEXT DEFAULT NULL, CHANGE confirmed_at confirmed_at DATETIME DEFAULT NULL, CHANGE cancelled_at cancelled_at DATETIME DEFAULT NULL, CHANGE cancellation_reason cancellation_reason VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B55114197E709F');
        $this->addSql('DROP TABLE signalement');
        $this->addSql('ALTER TABLE avis CHANGE status status VARCHAR(20) DEFAULT \'\'\'pending\'\'\' NOT NULL, CHANGE destination destination VARCHAR(255) DEFAULT \'NULL\', CHANGE latitude latitude DOUBLE PRECISION DEFAULT \'NULL\', CHANGE longitude longitude DOUBLE PRECISION DEFAULT \'NULL\', CHANGE weather_data weather_data LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE sentiment_analysis sentiment_analysis LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE keywords keywords LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE sentiment_score sentiment_score DOUBLE PRECISION DEFAULT \'NULL\', CHANGE predictive_analysis predictive_analysis LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE satisfaction_score satisfaction_score DOUBLE PRECISION DEFAULT \'NULL\', CHANGE commentaires commentaires LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE photos photos LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE main_photo main_photo VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('CREATE INDEX idx_avis_status ON avis (status)');
        $this->addSql('ALTER TABLE reservation CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE number_of_passengers number_of_passengers INT DEFAULT 1 NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'\'\'confirmed\'\'\' NOT NULL, CHANGE seat_number seat_number VARCHAR(10) DEFAULT \'NULL\', CHANGE boarding_pass_file boarding_pass_file VARCHAR(20) DEFAULT \'NULL\', CHANGE boarding_pass_sent boarding_pass_sent TINYINT DEFAULT 0, CHANGE admin_notes admin_notes TEXT DEFAULT NULL, CHANGE confirmed_at confirmed_at DATETIME DEFAULT \'NULL\', CHANGE cancelled_at cancelled_at DATETIME DEFAULT \'NULL\', CHANGE cancellation_reason cancellation_reason VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('CREATE INDEX idx_reservation_status ON reservation (status)');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
    }
}
