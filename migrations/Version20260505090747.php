<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505090747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE facture (id INT AUTO_INCREMENT NOT NULL, numero_facture VARCHAR(50) NOT NULL, date_emission DATETIME NOT NULL, montant_ht DOUBLE PRECISION NOT NULL, tva DOUBLE PRECISION NOT NULL, montant_ttc DOUBLE PRECISION NOT NULL, paiement_id INT NOT NULL, UNIQUE INDEX UNIQ_FE86641038D27AB1 (numero_facture), UNIQUE INDEX UNIQ_FE8664102A4C4478 (paiement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE paiements (id INT AUTO_INCREMENT NOT NULL, amount DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, date_paiement DATETIME NOT NULL, methode_paiement VARCHAR(20) NOT NULL, reservation_id INT DEFAULT NULL, nom VARCHAR(100) DEFAULT NULL, prenom VARCHAR(100) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, attempts INT DEFAULT 0 NOT NULL, score_risque DOUBLE PRECISION DEFAULT NULL, user_id INT NOT NULL, subscription_id INT DEFAULT NULL, INDEX IDX_E1B02E12A76ED395 (user_id), INDEX IDX_E1B02E129A1887DC (subscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation_transport (id INT AUTO_INCREMENT NOT NULL, date_reservation DATETIME NOT NULL, status VARCHAR(50) NOT NULL, is_paid TINYINT NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, transport_id INT NOT NULL, INDEX IDX_7CEC40B1A76ED395 (user_id), INDEX IDX_7CEC40B19909C13F (transport_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE signalement (id INT AUTO_INCREMENT NOT NULL, motif VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, statut VARCHAR(50) DEFAULT \'en_attente\' NOT NULL, created_at DATETIME NOT NULL, ip_address VARCHAR(255) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, email_signaleur VARCHAR(255) DEFAULT NULL, avis_id INT NOT NULL, INDEX IDX_F4B55114197E709F (avis_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, plan VARCHAR(50) NOT NULL, price NUMERIC(10, 2) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, status VARCHAR(20) NOT NULL, user_id INT NOT NULL, INDEX IDX_A3C664D3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transport (id INT AUTO_INCREMENT NOT NULL, compagnie VARCHAR(255) NOT NULL, numero VARCHAR(100) NOT NULL, capacite INT NOT NULL, prix DOUBLE PRECISION NOT NULL, image_url VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, trajet VARCHAR(255) DEFAULT NULL, date_heure DATETIME DEFAULT NULL, transport_type_id INT NOT NULL, vehicule_id INT DEFAULT NULL, INDEX IDX_66AB212E519B4C62 (transport_type_id), INDEX IDX_66AB212E4A4A3511 (vehicule_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transport_type (idType INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, prix_depart DOUBLE PRECISION NOT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY (idType)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vehicule (id INT AUTO_INCREMENT NOT NULL, marque VARCHAR(100) NOT NULL, matricule VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, capacite INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vote (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, date_vote DATETIME NOT NULL, avis_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5A108564197E709F (avis_id), INDEX IDX_5A108564A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE8664102A4C4478 FOREIGN KEY (paiement_id) REFERENCES paiements (id)');
        $this->addSql('ALTER TABLE paiements ADD CONSTRAINT FK_E1B02E12A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE paiements ADD CONSTRAINT FK_E1B02E129A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE reservation_transport ADD CONSTRAINT FK_7CEC40B1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reservation_transport ADD CONSTRAINT FK_7CEC40B19909C13F FOREIGN KEY (transport_id) REFERENCES transport (id)');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_F4B55114197E709F FOREIGN KEY (avis_id) REFERENCES avis (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT FK_66AB212E519B4C62 FOREIGN KEY (transport_type_id) REFERENCES transport_type (idType)');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT FK_66AB212E4A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564197E709F FOREIGN KEY (avis_id) REFERENCES avis (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE avis CHANGE destination destination VARCHAR(255) DEFAULT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT NULL, CHANGE longitude longitude DOUBLE PRECISION DEFAULT NULL, CHANGE weather_data weather_data JSON DEFAULT NULL, CHANGE photos photos JSON DEFAULT NULL, CHANGE main_photo main_photo VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'pending\' NOT NULL, CHANGE sentiment_analysis sentiment_analysis JSON DEFAULT NULL, CHANGE keywords keywords JSON DEFAULT NULL, CHANGE sentiment_score sentiment_score DOUBLE PRECISION DEFAULT NULL, CHANGE predictive_analysis predictive_analysis JSON DEFAULT NULL, CHANGE satisfaction_score satisfaction_score DOUBLE PRECISION DEFAULT NULL, CHANGE commentaires commentaires JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE destination CHANGE code_iata code_iata VARCHAR(3) DEFAULT NULL, CHANGE video_url video_url VARCHAR(500) DEFAULT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `FK_RESERVATION_DESTINATION`');
        $this->addSql('ALTER TABLE reservation DROP destination, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE airline airline VARCHAR(50) NOT NULL, CHANGE flight_number flight_number VARCHAR(20) NOT NULL, CHANGE departure_airport departure_airport VARCHAR(50) NOT NULL, CHANGE departure_time departure_time DATETIME NOT NULL, CHANGE arrival_airport arrival_airport VARCHAR(50) NOT NULL, CHANGE arrival_time arrival_time DATETIME NOT NULL, CHANGE price price DOUBLE PRECISION NOT NULL, CHANGE number_of_passengers number_of_passengers INT NOT NULL, CHANGE reservation_date reservation_date DATETIME NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'pending\' NOT NULL, CHANGE seat_number seat_number VARCHAR(10) DEFAULT NULL, CHANGE boarding_pass_file boarding_pass_file VARCHAR(20) DEFAULT NULL, CHANGE boarding_pass_sent boarding_pass_sent TINYINT NOT NULL, CHANGE admin_notes admin_notes LONGTEXT DEFAULT NULL, CHANGE confirmed_at confirmed_at DATETIME DEFAULT NULL, CHANGE cancelled_at cancelled_at DATETIME DEFAULT NULL, CHANGE cancellation_reason cancellation_reason VARCHAR(255) DEFAULT NULL, CHANGE destination_id destination_id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955816C6140 FOREIGN KEY (destination_id) REFERENCES destination (id)');
        $this->addSql('ALTER TABLE reservation RENAME INDEX fk_reservation_destination TO IDX_42C84955816C6140');
        $this->addSql('ALTER TABLE user ADD wallet_balance DOUBLE PRECISION NOT NULL, ADD loyalty_points INT NOT NULL, ADD pays VARCHAR(100) DEFAULT \'Tunisie\', ADD est_bloque TINYINT DEFAULT 0 NOT NULL, CHANGE roles roles JSON NOT NULL, CHANGE name name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649e7927c74 TO UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('ALTER TABLE voyages CHANGE imagePath imagePath VARCHAR(255) DEFAULT NULL, CHANGE pays_depart pays_depart VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE8664102A4C4478');
        $this->addSql('ALTER TABLE paiements DROP FOREIGN KEY FK_E1B02E12A76ED395');
        $this->addSql('ALTER TABLE paiements DROP FOREIGN KEY FK_E1B02E129A1887DC');
        $this->addSql('ALTER TABLE reservation_transport DROP FOREIGN KEY FK_7CEC40B1A76ED395');
        $this->addSql('ALTER TABLE reservation_transport DROP FOREIGN KEY FK_7CEC40B19909C13F');
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_F4B55114197E709F');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY FK_66AB212E519B4C62');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY FK_66AB212E4A4A3511');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564197E709F');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564A76ED395');
        $this->addSql('DROP TABLE facture');
        $this->addSql('DROP TABLE paiements');
        $this->addSql('DROP TABLE reservation_transport');
        $this->addSql('DROP TABLE signalement');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE transport');
        $this->addSql('DROP TABLE transport_type');
        $this->addSql('DROP TABLE vehicule');
        $this->addSql('DROP TABLE vote');
        $this->addSql('ALTER TABLE avis CHANGE status status VARCHAR(20) DEFAULT \'\'\'pending\'\'\' NOT NULL, CHANGE destination destination VARCHAR(255) DEFAULT \'NULL\', CHANGE latitude latitude DOUBLE PRECISION DEFAULT \'NULL\', CHANGE longitude longitude DOUBLE PRECISION DEFAULT \'NULL\', CHANGE weather_data weather_data LONGTEXT DEFAULT NULL, CHANGE sentiment_analysis sentiment_analysis LONGTEXT DEFAULT NULL, CHANGE keywords keywords LONGTEXT DEFAULT NULL, CHANGE sentiment_score sentiment_score DOUBLE PRECISION DEFAULT \'NULL\', CHANGE predictive_analysis predictive_analysis LONGTEXT DEFAULT NULL, CHANGE satisfaction_score satisfaction_score DOUBLE PRECISION DEFAULT \'NULL\', CHANGE commentaires commentaires LONGTEXT DEFAULT NULL, CHANGE photos photos LONGTEXT DEFAULT NULL, CHANGE main_photo main_photo VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE destination CHANGE code_iata code_iata VARCHAR(3) DEFAULT \'NULL\', CHANGE video_url video_url VARCHAR(500) DEFAULT \'NULL\', CHANGE date_creation date_creation DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955816C6140');
        $this->addSql('ALTER TABLE reservation ADD destination VARCHAR(100) NOT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE airline airline VARCHAR(50) DEFAULT \'NULL\', CHANGE flight_number flight_number VARCHAR(20) DEFAULT \'NULL\', CHANGE departure_airport departure_airport VARCHAR(50) DEFAULT \'NULL\', CHANGE departure_time departure_time DATETIME DEFAULT \'NULL\', CHANGE arrival_airport arrival_airport VARCHAR(50) DEFAULT \'NULL\', CHANGE arrival_time arrival_time DATETIME DEFAULT \'NULL\', CHANGE price price DOUBLE PRECISION DEFAULT \'NULL\', CHANGE number_of_passengers number_of_passengers INT DEFAULT NULL, CHANGE reservation_date reservation_date DATETIME DEFAULT \'NULL\', CHANGE status status VARCHAR(20) DEFAULT \'\'\'pending\'\'\' NOT NULL, CHANGE seat_number seat_number VARCHAR(10) DEFAULT \'NULL\', CHANGE boarding_pass_file boarding_pass_file VARCHAR(20) DEFAULT \'NULL\', CHANGE boarding_pass_sent boarding_pass_sent TINYINT DEFAULT 0 NOT NULL, CHANGE admin_notes admin_notes TEXT DEFAULT NULL, CHANGE confirmed_at confirmed_at DATETIME DEFAULT \'NULL\', CHANGE cancelled_at cancelled_at DATETIME DEFAULT \'NULL\', CHANGE cancellation_reason cancellation_reason VARCHAR(255) DEFAULT \'NULL\', CHANGE destination_id destination_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT `FK_RESERVATION_DESTINATION` FOREIGN KEY (destination_id) REFERENCES destination (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation RENAME INDEX idx_42c84955816c6140 TO FK_RESERVATION_DESTINATION');
        $this->addSql('ALTER TABLE `user` DROP wallet_balance, DROP loyalty_points, DROP pays, DROP est_bloque, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_identifier_email TO UNIQ_8D93D649E7927C74');
        $this->addSql('ALTER TABLE voyages CHANGE imagePath imagePath VARCHAR(255) DEFAULT \'NULL\', CHANGE pays_depart pays_depart VARCHAR(100) DEFAULT \'NULL\'');
    }
}
