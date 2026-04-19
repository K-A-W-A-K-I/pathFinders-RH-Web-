<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add persistent analytics fields for reclamation and utilisateur dashboards';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reclamation ADD COLUMN analytics_urgency_score DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN analytics_priority_level VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN analytics_anomaly_score DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN analytics_anomaly_flag TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN analytics_anomaly_reasons JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN analytics_last_calculated_at DATETIME DEFAULT NULL');

        $this->addSql('ALTER TABLE utilisateurs ADD COLUMN analytics_trust_score DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateurs ADD COLUMN analytics_trust_level VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateurs ADD COLUMN analytics_trust_flags JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateurs ADD COLUMN analytics_last_calculated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reclamation DROP COLUMN analytics_urgency_score');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN analytics_priority_level');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN analytics_anomaly_score');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN analytics_anomaly_flag');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN analytics_anomaly_reasons');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN analytics_last_calculated_at');

        $this->addSql('ALTER TABLE utilisateurs DROP COLUMN analytics_trust_score');
        $this->addSql('ALTER TABLE utilisateurs DROP COLUMN analytics_trust_level');
        $this->addSql('ALTER TABLE utilisateurs DROP COLUMN analytics_trust_flags');
        $this->addSql('ALTER TABLE utilisateurs DROP COLUMN analytics_last_calculated_at');
    }
}
