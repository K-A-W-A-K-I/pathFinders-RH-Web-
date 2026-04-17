<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add blacklist fields to candidats table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidats ADD COLUMN is_blacklisted TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE candidats ADD COLUMN blacklist_note TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE candidats ADD COLUMN blacklisted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidats DROP COLUMN is_blacklisted');
        $this->addSql('ALTER TABLE candidats DROP COLUMN blacklist_note');
        $this->addSql('ALTER TABLE candidats DROP COLUMN blacklisted_at');
    }
}
