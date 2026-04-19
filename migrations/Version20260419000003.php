<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add advanced moderation fields and admin reply tracking to reclamation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reclamation ADD COLUMN moderation_score DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN moderation_flags JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN moderation_summary LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN moderation_urgency VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN moderation_sentiment VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN manual_review_required TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN admin_reply LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN admin_reply_sent_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN admin_reply_email_status VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD COLUMN admin_reply_email_error LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reclamation DROP COLUMN moderation_score');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN moderation_flags');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN moderation_summary');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN moderation_urgency');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN moderation_sentiment');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN manual_review_required');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN admin_reply');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN admin_reply_sent_at');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN admin_reply_email_status');
        $this->addSql('ALTER TABLE reclamation DROP COLUMN admin_reply_email_error');
    }
}
