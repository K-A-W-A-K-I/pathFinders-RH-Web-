<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add geolocation, incident date, and moderation columns to reclamation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reclamation ADD COLUMN date_incident DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN lieu VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN latitude NUMERIC(10, 7) DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN longitude NUMERIC(10, 7) DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN moderation_decision VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN moderation_reason LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reclamation DROP COLUMN date_incident');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN lieu');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN latitude');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN longitude');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN moderation_decision');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN moderation_reason');
    }
}
