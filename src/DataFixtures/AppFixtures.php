<?php

namespace App\DataFixtures;

use App\Entity\Answer;
use App\Entity\Cours;
use App\Entity\Document;
use App\Entity\Note;
use App\Entity\Qcm;
use App\Entity\Question;
use App\Entity\Student;
use App\Entity\Teacher;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $teacher = new Teacher();
        $teacher->setEmail('prof@classroom.fr')
            ->setFirstName('Jean')
            ->setLastName('Professeur')
            ->setRoles(['ROLE_TEACHER'])
            ->setPassword($this->hasher->hashPassword($teacher, 'password'));
        $manager->persist($teacher);

        // 👨‍🎓 Élève
        $student = new Student();
        $student->setEmail('eleve@classroom.fr')
            ->setFirstName('Paul')
            ->setLastName('Etudiant')
            ->setRoles(['ROLE_STUDENT'])
            ->setPassword($this->hasher->hashPassword($student, 'password'));
        $manager->persist($student);

        $cours = new Cours();
        $cours->setTitle('Histoire de France')
            ->setContenu('Ce cours unique couvre l\'histoire de France')
            ->setTeacher($teacher);
        $manager->persist($cours);

        $doc = new Document();
        $doc->setTitle('Support de cours PDF')
            ->setPath('cours_demo.pdf')
            ->setCours($cours);
        $manager->persist($doc);

        $qcm = new Qcm();
        $qcm->setTitle('QCM Final')
            ->setCours($cours);
        $manager->persist($qcm);

        for ($q = 1; $q <= 5; $q++) {
            $question = new Question();
            $question->setEntitled('Question numéro ' . $q . ' ?');
            $question->setQcm($qcm);
            $manager->persist($question);

            $correct = new Answer();
            $correct->setText('La bonne réponse');
            $correct->setIsCorrect(true);
            $correct->setQuestion($question);
            $manager->persist($correct);

            for ($a = 0; $a < 3; $a++) {
                $wrong = new Answer();
                $wrong->setText('Une mauvaise réponse');
                $wrong->setIsCorrect(false);
                $wrong->setQuestion($question);
                $manager->persist($wrong);
            }
        }

        $note = new Note();
        $note->setStudent($student)
            ->setQcm($qcm)
            ->setScore(3)
            ->setAttemptedAt(new \DateTimeImmutable());
        $manager->persist($note);

        // Envoi en BDD
        $manager->flush();
    }
}