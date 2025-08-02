<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802104147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Ajouter colonnes nullable
        $this->addSql(<<<'SQL'
        ALTER TABLE article 
        ADD author_id INT DEFAULT NULL, 
        ADD created_at DATETIME DEFAULT NULL, 
        ADD updated_at DATETIME DEFAULT NULL, 
        ADD is_published TINYINT(1) NOT NULL DEFAULT 0
    SQL);

        // Mettre à jour les articles existants
        $this->addSql(<<<'SQL'
        UPDATE article SET created_at = NOW() WHERE created_at IS NULL
    SQL);

        // Rendre created_at obligatoire
        $this->addSql(<<<'SQL'
        ALTER TABLE article MODIFY created_at DATETIME NOT NULL
    SQL);

        // Ajouter contraintes
        $this->addSql(<<<'SQL'
        ALTER TABLE article ADD CONSTRAINT FK_23A0E66F675F31B FOREIGN KEY (author_id) REFERENCES user (id)
    SQL);

        $this->addSql(<<<'SQL'
        CREATE INDEX IDX_23A0E66F675F31B ON article (author_id)
    SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
        ALTER TABLE article DROP FOREIGN KEY FK_23A0E66F675F31B
    SQL);

        $this->addSql(<<<'SQL'
        DROP INDEX IDX_23A0E66F675F31B ON article
    SQL);

        $this->addSql(<<<'SQL'
        ALTER TABLE article DROP author_id, DROP created_at, DROP updated_at, DROP is_published
    SQL);
    }
}
