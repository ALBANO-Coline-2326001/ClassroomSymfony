<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Qcm;
use App\Entity\Question;
use App\Entity\Video;
use App\Service\GroqService; // On utilise le nouveau service
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
        GroqService $groqService,
        EntityManagerInterface $em,
        Request $request
    ): Response
    {
        // 1. Vérif clé API
        if (!$groqService->isConfigured()) {
            $this->addFlash('warning', 'Clé API Groq manquante.');
            return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
        }

        // 2. Paramètres
        $nbQuestions = (int) $request->request->get('nb_questions', 10);
        $type = $request->request->get('type', 'qcm');

        // 3. Récupération du fichier physique
        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/public/assets/video/' . $video->getUrl();

        if (!file_exists($filePath)) {
            $this->addFlash('danger', 'Fichier vidéo introuvable sur le serveur.');
            return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
        }

        // ⚠️ Attention : L'API Whisper limite souvent les fichiers à ~25Mo.
        // Si tes vidéos sont lourdes, ça plantera ici sans ffmpeg.
        if (filesize($filePath) > 25 * 1024 * 1024) {
            $this->addFlash('warning', 'Vidéo trop lourde pour l\'IA (>25Mo).');
            return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
        }

        // 4. Appel Magique au Service
        // Le service va : Lire la vidéo -> Transcrire en texte -> Générer le JSON
        $qcmData = $groqService->generateQcmFromVideoFile($filePath, $nbQuestions, $type);

        if (empty($qcmData)) {
            $this->addFlash('danger', 'Échec de la génération (Transcription ou IA vide).');
            return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
        }

        // 5. Sauvegarde (Classique Doctrine)
        $qcm = new Qcm();
        $qcm->setTitle("Quiz IA (" . ucfirst($type) . ") : " . $video->getTitle());
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
        $this->addFlash('success', 'QCM généré avec succès via Groq (Whisper + Llama) ! 🚀');

        return $this->redirectToRoute('app_cours_show', ['id' => $video->getCours()->getId()]);
    }
}