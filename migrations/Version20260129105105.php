<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129105105 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anwser (id INT AUTO_INCREMENT NOT NULL, text VARCHAR(255) NOT NULL, is_correct TINYINT NOT NULL, question_id INT DEFAULT NULL, INDEX IDX_52B675AE1E27F6BF (question_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cours (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, teacher_id INT DEFAULT NULL, INDEX IDX_FDCA8C9C41807E1D (teacher_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, cours_id INT DEFAULT NULL, INDEX IDX_D8698A767ECF78B0 (cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, score INT NOT NULL, attempted_at DATETIME NOT NULL, student_id INT DEFAULT NULL, qcm_id INT DEFAULT NULL, INDEX IDX_CFBDFA14CB944F1A (student_id), INDEX IDX_CFBDFA14FF6241A6 (qcm_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE qcm (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, cours_id INT DEFAULT NULL, INDEX IDX_D7A1FEF47ECF78B0 (cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, entitled VARCHAR(255) NOT NULL, qcm_id INT DEFAULT NULL, INDEX IDX_B6F7494EFF6241A6 (qcm_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE video (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, duration INT NOT NULL, cours_id INT DEFAULT NULL, INDEX IDX_7CC7DA2C7ECF78B0 (cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE anwser ADD CONSTRAINT FK_52B675AE1E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_FDCA8C9C41807E1D FOREIGN KEY (teacher_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A767ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14FF6241A6 FOREIGN KEY (qcm_id) REFERENCES qcm (id)');
        $this->addSql('ALTER TABLE qcm ADD CONSTRAINT FK_D7A1FEF47ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494EFF6241A6 FOREIGN KEY (qcm_id) REFERENCES qcm (id)');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2C7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
        $this->addSql('ALTER TABLE user ADD type VARCHAR(255) NOT NULL, ADD role VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anwser DROP FOREIGN KEY FK_52B675AE1E27F6BF');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_FDCA8C9C41807E1D');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A767ECF78B0');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14CB944F1A');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14FF6241A6');
        $this->addSql('ALTER TABLE qcm DROP FOREIGN KEY FK_D7A1FEF47ECF78B0');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494EFF6241A6');
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2C7ECF78B0');
        $this->addSql('DROP TABLE anwser');
        $this->addSql('DROP TABLE cours');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE qcm');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE video');
        $this->addSql('ALTER TABLE user DROP type, DROP role');
    }
}
