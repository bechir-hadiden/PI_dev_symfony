<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create abonnement table and add paiement.abonnement_id relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE abonnement (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type VARCHAR(50) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, status VARCHAR(20) NOT NULL, prix DOUBLE PRECISION NOT NULL, INDEX IDX_ABONNEMENT_USER_ID (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT FK_ABONNEMENT_USER FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE paiement ADD abonnement_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_PAIEMENT_ABONNEMENT_ID ON paiement (abonnement_id)');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_PAIEMENT_ABONNEMENT FOREIGN KEY (abonnement_id) REFERENCES abonnement (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_PAIEMENT_ABONNEMENT');
        $this->addSql('DROP INDEX IDX_PAIEMENT_ABONNEMENT_ID ON paiement');
        $this->addSql('ALTER TABLE paiement DROP abonnement_id');
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY FK_ABONNEMENT_USER');
        $this->addSql('DROP INDEX IDX_ABONNEMENT_USER_ID ON abonnement');
        $this->addSql('DROP TABLE abonnement');
    }
}
