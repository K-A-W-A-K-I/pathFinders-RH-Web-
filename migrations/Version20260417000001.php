<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add interview_token and interview_score to entretiens table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entretiens ADD COLUMN interview_token VARCHAR(64) DEFAULT NULL UNIQUE');
        $this->addSql('ALTER TABLE entretiens ADD COLUMN interview_score INT DEFAULT NULL');
        $this->addSql('ALTER TABLE entretiens ADD COLUMN interview_completed TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entretiens DROP COLUMN interview_token');
        $this->addSql('ALTER TABLE entretiens DROP COLUMN interview_score');
        $this->addSql('ALTER TABLE entretiens DROP COLUMN interview_completed');
    }
}
