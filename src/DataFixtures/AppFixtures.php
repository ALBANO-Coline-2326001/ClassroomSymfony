<?php

namespace App\DataFixtures;

use App\Entity\Answer;
use App\Entity\Cours;
use App\Entity\Question;
use App\Entity\Qcm;
use App\Entity\Note;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Video;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // --- 1. CRÉATION DU PROFESSEUR (Admin du contenu) 👨‍🏫 ---
        $teacher = new Teacher();
        $teacher->setEmail('prof@edulearn.fr')
            ->setFirstName('Jean')
            ->setLastName('Dubois')
            ->setRoles(['ROLE_TEACHER'])
            ->setPassword($this->hasher->hashPassword($teacher, 'password'));

        $manager->persist($teacher);

        // --- 2. CRÉATION DES COURS (Basé sur ta maquette) 📚 ---
        $coursesData = [
            ['Introduction à Symfony', 'Les bases du framework PHP.'],
            ['Security Bundle', 'Gérer l\'authentification et les rôles.'],
            ['API Platform', 'Créer une API REST performante.'],
            ['Doctrine ORM', 'Maitriser la base de données relationnelle.']
        ];

        $allQuizzes = []; // On stocke les quiz créés pour générer des notes après

        foreach ($coursesData as $cData) {
            $course = new Cours();
            $course->setTitle($cData[0])
                ->setContenu($cData[1])
                ->setTeacher($teacher);
            $manager->persist($course);

            // A. Ajout de Vidéos et Documents
            for ($i = 1; $i <= 3; $i++) {
                $video = new Video();
                $video->setTitle("Chapitre $i : " . $faker->sentence(3))
                    ->setDuration($faker->numberBetween(10, 60)) // Durée en minutes
                    ->setUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ') // Fake URL
                    ->setCours($course);
                $manager->persist($video);
            }

            // B. Ajout du QCM (Généré par "IA") 🤖
            $quiz = new Qcm();
            $quiz->setTitle('QCM - ' . $cData[0])
                ->setCours($course);
            $manager->persist($quiz);
            $allQuizzes[] = $quiz; // Sauvegarde pour plus tard

            // C. Ajout des Questions/Réponses
            for ($q = 0; $q < 5; $q++) { // 5 Questions par Quiz
                $question = new Question();
                $question->setEntitled($faker->sentence(10) . ' ?') // Phrase interrogative
                ->setQcm($quiz);
                $manager->persist($question);

                // 1 Bonne réponse ✅
                $correctAnswer = new Answer();
                $correctAnswer->setText($faker->sentence(4))
                    ->setIsCorrect(true)
                    ->setQuestion($question);
                $manager->persist($correctAnswer);

                // 3 Mauvaises réponses ❌
                for ($a = 0; $a < 3; $a++) {
                    $wrongAnswer = new Answer();
                    $wrongAnswer->setText($faker->sentence(4))
                        ->setIsCorrect(false)
                        ->setQuestion($question);
                    $manager->persist($wrongAnswer);
                }
            }
        }

        // --- 3. CRÉATION DES ÉTUDIANTS (Alice, Thomas...) 👩‍🎓 ---
        $studentsData = [
            ['Alice', 'Martin'], ['Thomas', 'Dubois'],
            ['Sophie', 'Bernard'], ['Lucas', 'Laurent']
        ];

        foreach ($studentsData as $sData) {
            $student = new Student();
            $student->setEmail(strtolower($sData[0]) . '.' . strtolower($sData[1]) . '@etu.univ.fr')
                ->setFirstName($sData[0])
                ->setLastName($sData[1])
                ->setRoles(['ROLE_STUDENT'])
                ->setPassword($this->hasher->hashPassword($student, 'password'));
            $manager->persist($student);

            // --- 4. SIMULATION DES RÉSULTATS (QuizAttempt) 📊 ---
            // Chaque étudiant passe 2 quiz au hasard
            for ($k = 0; $k < 2; $k++) {
                $attempt = new Note();
                $attempt->setStudent($student)
                    ->setQcm($faker->randomElement($allQuizzes)) // Un quiz au pif
                    ->setScore($faker->numberBetween(8, 20)) // Note entre 8 et 20
                    ->setAttemptedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 month', 'now')));

                $manager->persist($attempt);
            }
        }

        $manager->flush(); // Envoi final vers MySQL
    }
}