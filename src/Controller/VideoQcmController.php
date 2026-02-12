<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Qcm;
use App\Entity\Question;
use App\Entity\Video;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TEACHER')]
class VideoQcmController extends AbstractController
{
    #[Route('/video/{id}/generate-ai', name: 'app_video_generate_ai', methods: ['POST'])]
    public function generate(
        Video $video,
        GeminiService $geminiService,
        EntityManagerInterface $em,
        Request $request
    ): Response
    {
        if (!$geminiService->isConfigured()) {
            $this->addFlash('warning', 'Clé API Gemini manquante.');
            return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
        }

        set_time_limit(600);

        $nbQuestions = (int) $request->request->get('nb_questions', 10);
        $type = $request->request->get('type', 'qcm');

        if ($nbQuestions < 1 || $nbQuestions > 20) $nbQuestions = 10;

        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/public/assets/video/' . $video->getUrl();

        if (!file_exists($filePath)) {
            dd("ERREUR CRITIQUE : Le fichier vidéo n'existe pas au chemin : " . $filePath);
        }

        if (!file_exists($filePath)) {
            $this->addFlash('danger', 'Vidéo introuvable.');
            return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
        }

        $qcmData = $geminiService->generateQcmFromVideo($filePath, $nbQuestions, $type);

        if (empty($qcmData)) {
            $this->addFlash('danger', 'L\'IA n\'a pas pu analyser la vidéo.');
            return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
        }

        $typeLabel = ($type === 'vrai_faux') ? 'Vrai/Faux' : 'QCM';

        $qcm = new Qcm();
        $qcm->setTitle("Vidéo $typeLabel IA ($nbQuestions q.) : " . $video->getTitle());
        $qcm->setCours($video->getCours());
        $em->persist($qcm);

        foreach ($qcmData as $qData) {
            $question = new Question();
            $question->setEntitled($qData['question']);
            $qcm->addQuestion($question);
            $em->persist($question);

            $answers = $qData['answers'] ?? [];
            shuffle($answers);

            foreach ($answers as $aData) {
                $answer = new Answer();
                $answer->setText($aData['text']);
                $answer->setIsCorrect((bool)$aData['isCorrect']);
                $question->addAnswer($answer);
                $em->persist($answer);
            }
        }

        $em->flush();
        $this->addFlash('success', 'QCM vidéo généré avec succès !');

        return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
    }
}