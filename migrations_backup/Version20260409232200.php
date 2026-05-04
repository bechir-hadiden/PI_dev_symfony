<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409232200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, date_reservation DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, nombre_places INT NOT NULL, nom_client VARCHAR(255) DEFAULT NULL, email_client VARCHAR(255) DEFAULT NULL, transport_id INT NOT NULL, INDEX IDX_42C849559909C13F (transport_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849559909C13F FOREIGN KEY (transport_id) REFERENCES transport (id)');
        $this->addSql('ALTER TABLE bookings DROP FOREIGN KEY `bookings_ibfk_1`');
        $this->addSql('ALTER TABLE bookings DROP FOREIGN KEY `bookings_ibfk_2`');
        $this->addSql('ALTER TABLE bookings DROP FOREIGN KEY `bookings_ibfk_3`');
        $this->addSql('ALTER TABLE code_promo DROP FOREIGN KEY `fk_code_offre`');
        $this->addSql('ALTER TABLE hotel_amenities DROP FOREIGN KEY `hotel_amenities_ibfk_1`');
        $this->addSql('ALTER TABLE hotel_images DROP FOREIGN KEY `hotel_images_ibfk_1`');
        $this->addSql('ALTER TABLE hotel_policies DROP FOREIGN KEY `hotel_policies_ibfk_1`');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY `notification_ibfk_1`');
        $this->addSql('ALTER TABLE room_amenities DROP FOREIGN KEY `room_amenities_ibfk_1`');
        $this->addSql('ALTER TABLE room_images DROP FOREIGN KEY `room_images_ibfk_1`');
        $this->addSql('ALTER TABLE room_types DROP FOREIGN KEY `room_types_ibfk_1`');
        $this->addSql('ALTER TABLE transport_reservation DROP FOREIGN KEY `transport_reservation_ibfk_1`');
        $this->addSql('DROP TABLE bookings');
        $this->addSql('DROP TABLE code_promo');
        $this->addSql('DROP TABLE hotels');
        $this->addSql('DROP TABLE hotel_amenities');
        $this->addSql('DROP TABLE hotel_images');
        $this->addSql('DROP TABLE hotel_policies');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE paiements');
        $this->addSql('DROP TABLE plans');
        $this->addSql('DROP TABLE profession');
        $this->addSql('DROP TABLE room_amenities');
        $this->addSql('DROP TABLE room_images');
        $this->addSql('DROP TABLE room_types');
        $this->addSql('DROP TABLE transport_catalog');
        $this->addSql('DROP TABLE transport_reservation');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE vehicule');
        $this->addSql('DROP TABLE vols');
        $this->addSql('DROP TABLE voyage');
        $this->addSql('DROP TABLE wallet_auto_recharge_attempts');
        $this->addSql('DROP TABLE wallet_auto_recharge_config');
        $this->addSql('DROP TABLE wallet_transactions');
        $this->addSql('ALTER TABLE destination ADD description LONGTEXT DEFAULT NULL, ADD video_url VARCHAR(500) DEFAULT NULL, ADD date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD `order` INT DEFAULT NULL, CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE pays pays VARCHAR(100) NOT NULL, CHANGE image_url image_url VARCHAR(500) DEFAULT NULL, CHANGE code_iata code_iata VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE transport ADD compagnie VARCHAR(255) NOT NULL, ADD numero VARCHAR(100) NOT NULL, ADD capacite INT NOT NULL, ADD prix DOUBLE PRECISION NOT NULL, ADD image_url VARCHAR(255) DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, ADD transport_type_id INT NOT NULL, DROP name, DROP type, DROP origin, DROP destination, DROP price, DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT FK_66AB212E519B4C62 FOREIGN KEY (transport_type_id) REFERENCES transport_type (id)');
        $this->addSql('CREATE INDEX IDX_66AB212E519B4C62 ON transport (transport_type_id)');
        $this->addSql('DROP INDEX nom ON transport_type');
        $this->addSql('ALTER TABLE transport_type MODIFY idType INT NOT NULL');
        $this->addSql('ALTER TABLE transport_type CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE prix_depart prix_depart DOUBLE PRECISION NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE idType id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE voyages ADD pavs_depart VARCHAR(100) DEFAULT NULL, DROP pays_depart, CHANGE imagePath imagePath VARCHAR(255) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE voyages ADD CONSTRAINT FK_30F7F9816C6140 FOREIGN KEY (destination_id) REFERENCES destination (id)');
        $this->addSql('CREATE INDEX IDX_30F7F9816C6140 ON voyages (destination_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bookings (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, hotel_id INT NOT NULL, room_type_id INT DEFAULT NULL, check_in_date DATE NOT NULL, check_out_date DATE NOT NULL, guests INT NOT NULL, total_price NUMERIC(10, 2) NOT NULL, status ENUM(\'PENDING\', \'CONFIRMED\', \'CANCELLED\') CHARACTER SET utf8mb4 DEFAULT \'\'\'PENDING\'\'\' COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX idx_user (user_id), INDEX idx_hotel (hotel_id), INDEX idx_status (status), INDEX room_type_id (room_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE code_promo (id_code INT AUTO_INCREMENT NOT NULL, code_texte VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_expiration DATE NOT NULL, id_offre INT NOT NULL, UNIQUE INDEX code_texte (code_texte), INDEX fk_code_offre (id_offre), PRIMARY KEY (id_code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE hotels (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, location VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, city VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, country VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, price_per_night NUMERIC(10, 2) NOT NULL, price_per_week NUMERIC(10, 2) DEFAULT \'NULL\', rating NUMERIC(2, 1) DEFAULT \'0.0\', review_count INT DEFAULT 0, check_in_time VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, check_out_time VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, contact_email VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, contact_phone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX idx_country (country), INDEX idx_rating (rating), FULLTEXT INDEX idx_search (name, city, country, location), INDEX idx_city (city), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE hotel_amenities (id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, amenity_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX idx_hotel (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE hotel_images (id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, image_url VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, display_order INT DEFAULT 0, INDEX idx_hotel (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE hotel_policies (id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, policy_text VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX idx_hotel (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, reservation_id INT DEFAULT NULL, message TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_sent DATETIME DEFAULT \'current_timestamp()\', is_read TINYINT DEFAULT 0, type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'CANCELLATION\'\'\' COLLATE `utf8mb4_general_ci`, INDEX user_id (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE offre (id_offre INT AUTO_INCREMENT NOT NULL, titre VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, taux_remise INT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, statut ENUM(\'ACTIVE\', \'EXPIREE\') CHARACTER SET utf8mb4 DEFAULT \'\'\'ACTIVE\'\'\' COLLATE `utf8mb4_general_ci`, id_voyage INT NOT NULL, is_local_support TINYINT DEFAULT 0, image_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\'\'default.jpg\'\'\' COLLATE `utf8mb4_general_ci`, id_hotel INT DEFAULT NULL, id_vol BIGINT DEFAULT NULL, id_vehicule INT DEFAULT NULL, category ENUM(\'VOYAGE\', \'HOTEL\', \'VOL\', \'TRANSPORT\') CHARACTER SET utf8mb4 DEFAULT \'\'\'VOYAGE\'\'\' COLLATE `utf8mb4_general_ci`, INDEX fk_offre_hotel (id_hotel), INDEX fk_offre_vol (id_vol), INDEX fk_offre_vehicule (id_vehicule), INDEX fk_offre_voyage (id_voyage), PRIMARY KEY (id_offre)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE paiements (id_paiement INT AUTO_INCREMENT NOT NULL, date_paiement DATE NOT NULL, montant NUMERIC(10, 2) NOT NULL, statut_paiement VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'\'\'En attente\'\'\' NOT NULL COLLATE `utf8mb4_general_ci`, methode_paiement VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'Carte Bancaire\'\'\' COLLATE `utf8mb4_general_ci`, stripe_session_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, user_id INT DEFAULT NULL, booking_id INT DEFAULT NULL, INDEX user_id (user_id), PRIMARY KEY (id_paiement)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE plans (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, price NUMERIC(10, 2) NOT NULL, duration_type VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE profession (idProfession INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (idProfession)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE room_amenities (id INT AUTO_INCREMENT NOT NULL, room_type_id INT NOT NULL, amenity_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX idx_room_type (room_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE room_images (id INT AUTO_INCREMENT NOT NULL, room_type_id INT NOT NULL, image_url VARCHAR(500) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX idx_room_type (room_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE room_types (id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, max_occupancy INT DEFAULT 2 NOT NULL, price_per_night NUMERIC(10, 2) NOT NULL, is_available TINYINT DEFAULT 1, INDEX idx_hotel (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE transport_catalog (idTransport INT AUTO_INCREMENT NOT NULL, type VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, compagnie VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, numero VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, capacite INT NOT NULL, imageUrl VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, prix NUMERIC(10, 2) DEFAULT \'NULL\', PRIMARY KEY (idTransport)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE transport_reservation (idReservation INT AUTO_INCREMENT NOT NULL, idUser INT NOT NULL, idTransport INT DEFAULT NULL, typeTransport VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, idVehicule INT DEFAULT NULL, dateReservation DATETIME DEFAULT \'current_timestamp()\', statut VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'\'\'CONFIRMED\'\'\' COLLATE `utf8mb4_general_ci`, INDEX idUser (idUser), PRIMARY KEY (idReservation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, password_hash VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, full_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, phone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, avatar VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, role ENUM(\'ADMIN\', \'CLIENT\') CHARACTER SET utf8mb4 DEFAULT \'\'\'CLIENT\'\'\' NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, id_profession INT DEFAULT 0, telephone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, wallet_balance DOUBLE PRECISION DEFAULT \'0\', loyalty_points INT DEFAULT 0, idProfession INT DEFAULT 0, password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\'\'\'\'\' COLLATE `utf8mb4_unicode_ci`, INDEX idx_email (email), UNIQUE INDEX username (username), INDEX idx_role (role), UNIQUE INDEX email (email), INDEX idx_username (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE vehicule (idVehicule INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, compagnie VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, numero VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, capacite INT DEFAULT NULL, prix DOUBLE PRECISION DEFAULT \'NULL\', disponible TINYINT DEFAULT NULL, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, latitude DOUBLE PRECISION DEFAULT \'NULL\', longitude DOUBLE PRECISION DEFAULT \'NULL\', ville VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, PRIMARY KEY (idVehicule)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE vols (id BIGINT AUTO_INCREMENT NOT NULL, compagnie VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, depart VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, arrivee VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_depart VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_arrivee VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, heure_depart VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, heure_arrivee VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, prix DOUBLE PRECISION NOT NULL, devise VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, escales INT DEFAULT 0, duree VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, date_creation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, image_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\'\'plane.jpg\'\'\' COLLATE `utf8mb4_general_ci`, INDEX idx_date (date_depart), INDEX idx_route (depart, arrivee), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE voyage (id_voyage INT AUTO_INCREMENT NOT NULL, destination VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id_voyage)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE wallet_auto_recharge_attempts (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, trigger_tx_type VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, balance_before NUMERIC(12, 2) NOT NULL, threshold_amount NUMERIC(12, 2) NOT NULL, recharge_amount NUMERIC(12, 2) NOT NULL, payment_method VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, status VARCHAR(24) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, reason VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX idx_wallet_auto_attempt_user_date (user_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE wallet_auto_recharge_config (user_id INT NOT NULL, enabled TINYINT DEFAULT 0 NOT NULL, threshold_amount NUMERIC(12, 2) DEFAULT \'30.00\' NOT NULL, recharge_amount NUMERIC(12, 2) DEFAULT \'50.00\' NOT NULL, max_recharges_per_day INT DEFAULT 3 NOT NULL, cooldown_minutes INT DEFAULT 10 NOT NULL, payment_method VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT \'\'\'STRIPE\'\'\' NOT NULL COLLATE `utf8mb4_general_ci`, updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY (user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE wallet_transactions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, tx_type VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, amount NUMERIC(12, 2) NOT NULL, signed_amount NUMERIC(12, 2) NOT NULL, metadata VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX idx_wallet_tx_user_date (user_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE bookings ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bookings ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (hotel_id) REFERENCES hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bookings ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (room_type_id) REFERENCES room_types (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE code_promo ADD CONSTRAINT `fk_code_offre` FOREIGN KEY (id_offre) REFERENCES offre (id_offre) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hotel_amenities ADD CONSTRAINT `hotel_amenities_ibfk_1` FOREIGN KEY (hotel_id) REFERENCES hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hotel_images ADD CONSTRAINT `hotel_images_ibfk_1` FOREIGN KEY (hotel_id) REFERENCES hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hotel_policies ADD CONSTRAINT `hotel_policies_ibfk_1` FOREIGN KEY (hotel_id) REFERENCES hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE room_amenities ADD CONSTRAINT `room_amenities_ibfk_1` FOREIGN KEY (room_type_id) REFERENCES room_types (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE room_images ADD CONSTRAINT `room_images_ibfk_1` FOREIGN KEY (room_type_id) REFERENCES room_types (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE room_types ADD CONSTRAINT `room_types_ibfk_1` FOREIGN KEY (hotel_id) REFERENCES hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transport_reservation ADD CONSTRAINT `transport_reservation_ibfk_1` FOREIGN KEY (idUser) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849559909C13F');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE destination DROP description, DROP video_url, DROP date_creation, DROP `order`, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE pays pays VARCHAR(255) NOT NULL, CHANGE code_iata code_iata VARCHAR(10) DEFAULT \'NULL\', CHANGE image_url image_url VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY FK_66AB212E519B4C62');
        $this->addSql('DROP INDEX IDX_66AB212E519B4C62 ON transport');
        $this->addSql('ALTER TABLE transport ADD name VARCHAR(200) NOT NULL, ADD type ENUM(\'FLIGHT\', \'TRAIN\', \'BUS\', \'CAR_RENTAL\') NOT NULL, ADD destination VARCHAR(100) NOT NULL, ADD price NUMERIC(10, 2) NOT NULL, ADD created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, ADD updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, DROP compagnie, DROP capacite, DROP prix, DROP image_url, DROP description, DROP transport_type_id, CHANGE numero origin VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE transport_type MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE transport_type CHANGE nom nom VARCHAR(50) NOT NULL, CHANGE prix_depart prix_depart DOUBLE PRECISION DEFAULT \'NULL\', CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE id idType INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idType)');
        $this->addSql('CREATE UNIQUE INDEX nom ON transport_type (nom)');
        $this->addSql('ALTER TABLE voyages DROP FOREIGN KEY FK_30F7F9816C6140');
        $this->addSql('DROP INDEX IDX_30F7F9816C6140 ON voyages');
        $this->addSql('ALTER TABLE voyages ADD pays_depart VARCHAR(50) DEFAULT \'NULL\', DROP pavs_depart, CHANGE imagePath imagePath VARCHAR(255) DEFAULT \'NULL\', CHANGE description description TEXT DEFAULT NULL');
    }
}
