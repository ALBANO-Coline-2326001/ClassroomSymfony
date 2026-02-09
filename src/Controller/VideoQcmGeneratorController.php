<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Qcm;
use App\Entity\Question;
use App\Entity\Video;
use App\Service\MistralService;
use App\Service\TranscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TEACHER')]
class VideoQcmGeneratorController extends AbstractController
{
    #[Route('/video/{id}/generate-qcm', name: 'app_video_generate_qcm')]
    public function generateFromVideo(
        Video $video,
        TranscriptionService $transcriptionService,
        MistralService $mistralService,
        EntityManagerInterface $em
    ): Response
    {
        set_time_limit(600);

        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/public/assets/video/' . $video->getUrl();

        if (!file_exists($filePath)) {
            $this->addFlash('danger', 'Fichier vidéo introuvable.');
            return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
        }

        try {
            $transcriptText = $transcriptionService->transcribeVideo($filePath);

            if (empty($transcriptText)) {
                throw new \Exception("La transcription est vide.");
            }

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la transcription : ' . $e->getMessage());
            return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
        }

        $qcmData = $mistralService->generateQcmFromText($transcriptText);

        if (empty($qcmData)) {
            $this->addFlash('danger', 'Mistral n\'a pas pu générer le QCM.');
            return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
        }

        $qcm = new Qcm();
        $qcm->setTitle('QCM Vidéo : ' . $video->getTitle());
        $qcm->setCours($video->getCours());
        $em->persist($qcm);

        foreach ($qcmData as $qData) {
            if (empty($qData['question'])) continue;

            $question = new Question();
            $question->setEntitled($qData['question']);
            $qcm->addQuestion($question);
            $em->persist($question);

            $answersData = $qData['answers'] ?? [];
            shuffle($answersData);

            foreach ($answersData as $aData) {
                $answer = new Answer();
                $answer->setText($aData['text'] ?? 'Réponse vide');
                $answer->setIsCorrect((bool)($aData['isCorrect'] ?? false));
                $question->addAnswer($answer);
                $em->persist($answer);
            }
        }

        $em->flush();

        $this->addFlash('success', 'QCM généré avec succès à partir de la vidéo !');
        return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
    }
}