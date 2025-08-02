<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802135003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Ajouter colonnes comme NULLABLE d'abord
        $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD last_login_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD is_active TINYINT(1) NOT NULL DEFAULT 1');

        // Remplir users existants
        $this->addSql('UPDATE user SET created_at = NOW() WHERE created_at IS NULL');

        // Rendre created_at obligatoire APRÈS
        $this->addSql('ALTER TABLE user MODIFY created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP created_at, DROP updated_at, DROP last_login_at, DROP is_active
        SQL);
    }
}
