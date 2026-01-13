<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107120018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // make this migration idempotent: check for table/column existence before making changes
        $sm = $this->connection->createSchemaManager();

        // anime
        if ($sm->tablesExist(['anime'])) {
            $this->addSql('ALTER TABLE anime CHANGE cover_url cover_url VARCHAR(255) DEFAULT NULL, CHANGE rating rating DOUBLE PRECISION DEFAULT NULL, CHANGE extra_payload extra_payload JSON DEFAULT NULL, CHANGE last_synced_at last_synced_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE external_id external_id VARCHAR(100) DEFAULT NULL');
        }

        // api_update
        if ($sm->tablesExist(['api_update'])) {
            $this->addSql('ALTER TABLE api_update CHANGE payload payload JSON NOT NULL');
        }

        // episode
        if ($sm->tablesExist(['episode'])) {
            $this->addSql('ALTER TABLE episode CHANGE air_date air_date DATE DEFAULT NULL, CHANGE thumbnail_url thumbnail_url VARCHAR(255) DEFAULT NULL, CHANGE external_id external_id VARCHAR(100) DEFAULT NULL');
        }

        // favorite
        if ($sm->tablesExist(['favorite'])) {
            $cols = array_map(fn($c) => $c->getName(), $sm->listTableColumns('favorite'));
            if (!in_array('mal_id', $cols, true)) {
                $this->addSql('ALTER TABLE favorite ADD mal_id INT DEFAULT NULL');
            }
            if (!in_array('title', $cols, true)) {
                $this->addSql('ALTER TABLE favorite ADD title VARCHAR(255) DEFAULT NULL');
            }
            if (!in_array('image', $cols, true)) {
                $this->addSql('ALTER TABLE favorite ADD image VARCHAR(500) DEFAULT NULL');
            }
            if (in_array('anime_id', $cols, true)) {
                $this->addSql('ALTER TABLE favorite CHANGE anime_id anime_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
            }
            if (in_array('created_at', $cols, true)) {
                $this->addSql('ALTER TABLE favorite CHANGE created_at added_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            }
        }

        // library
        if ($sm->tablesExist(['library'])) {
            $this->addSql('ALTER TABLE library CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }

        // library_item
        if ($sm->tablesExist(['library_item'])) {
            $cols = array_map(fn($c) => $c->getName(), $sm->listTableColumns('library_item'));
            if (!in_array('mal_id', $cols, true)) {
                $this->addSql('ALTER TABLE library_item ADD mal_id INT DEFAULT NULL');
            }
            if (!in_array('title', $cols, true)) {
                $this->addSql('ALTER TABLE library_item ADD title VARCHAR(255) DEFAULT NULL');
            }
            if (!in_array('image', $cols, true)) {
                $this->addSql('ALTER TABLE library_item ADD image VARCHAR(500) DEFAULT NULL');
            }
            if (in_array('anime_id', $cols, true)) {
                $this->addSql('ALTER TABLE library_item CHANGE anime_id anime_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
            }
            if (in_array('updated_at', $cols, true)) {
                $this->addSql('ALTER TABLE library_item CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
            }
        }

        // progress
        if ($sm->tablesExist(['progress'])) {
            $this->addSql('ALTER TABLE progress CHANGE seen_at seen_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }

        // user
        if ($sm->tablesExist(['user'])) {
            $this->addSql('ALTER TABLE user CHANGE first_name first_name VARCHAR(100) DEFAULT NULL, CHANGE last_name last_name VARCHAR(100) DEFAULT NULL, CHANGE roles roles JSON NOT NULL, CHANGE avatar_url avatar_url VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anime CHANGE cover_url cover_url VARCHAR(255) DEFAULT \'NULL\', CHANGE rating rating DOUBLE PRECISION DEFAULT \'NULL\', CHANGE extra_payload extra_payload LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE last_synced_at last_synced_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\', CHANGE external_id external_id VARCHAR(100) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE api_update CHANGE payload payload LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE episode CHANGE air_date air_date DATE DEFAULT \'NULL\', CHANGE thumbnail_url thumbnail_url VARCHAR(255) DEFAULT \'NULL\', CHANGE external_id external_id VARCHAR(100) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE favorite DROP mal_id, DROP title, DROP image, CHANGE anime_id anime_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE added_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE library CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE library_item DROP mal_id, DROP title, DROP image, CHANGE anime_id anime_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE progress CHANGE seen_at seen_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE `user` CHANGE first_name first_name VARCHAR(100) DEFAULT \'NULL\', CHANGE last_name last_name VARCHAR(100) DEFAULT \'NULL\', CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE avatar_url avatar_url VARCHAR(255) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
