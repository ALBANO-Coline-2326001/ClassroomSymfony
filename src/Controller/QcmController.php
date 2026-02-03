<?php

namespace App\Controller;

use App\Entity\Qcm;
use App\Entity\Cours;
use App\Entity\Question;
use App\Entity\Answer;
use App\Service\QuizGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QcmController extends AbstractController {

    public function generate (
        Cours $cours,
        QuizGeneratorService $quizGeneratorService,
        EntityManagerInterface $entityManager
    ): Response {
        $aiData = $quizGeneratorService->generateFromContent($cours->getDescription());

        $qcm = new Qcm();
        $qcm->setTitle("Qcm : " . $cours->getTitle());
        $qcm->setCours($cours);

        foreach ($aiData['questions'] as $qData) {
            $question = new Question();
            $question->setTitle($qData['title']);
            $qcm->addQuestion($question);

            foreach ($qData['answers'] as $aData) {
                $answer = new Answer();
                $answer->setTitle($aData['text']);
                $answer->setIsCorrect($aData['isCorrect']);
                $question->addAnswer($answer);

                $entityManager->persist($answer);
            }
            $entityManager->persist($question);
        }

        $entityManager->persist($qcm);
        $entityManager->flush();
        $this->addFlash('success', 'QCM généré avec succès !');

        return $this->redirectToRoute('app_qcm_index');
    }
}
