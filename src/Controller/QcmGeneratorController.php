<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Document;
use App\Entity\Qcm;
use App\Entity\Question;
use App\Service\MistralService;
use Doctrine\ORM\EntityManagerInterface;
use Smalot\PdfParser\Parser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TEACHER')]
class QcmGeneratorController extends AbstractController

{
    #[Route('/document/{id}/generate-qcm', name: 'app_document_generate_qcm', methods: ['POST'])]
    public function generateFromDocument(
        Document $document,
        MistralService $mistralService,
        EntityManagerInterface $em,
        Request $request
    ): Response
    {
        set_time_limit(400);

        $nbQuestions = (int) $request->request->get('nb_questions', 10);
        $type = $request->request->get('type', 'qcm');

        if ($nbQuestions < 1 || $nbQuestions > 20) $nbQuestions = 10;

        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/public/assets/document/' . $document->getPath();

        if (!file_exists($filePath)) {
            $this->addFlash('danger', 'Fichier introuvable.');
            return $this->redirectToRoute('app_cours_show', ['id' => $document->getCours()->getId()]);
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lecture PDF.');
            return $this->redirectToRoute('app_cours_show', ['id' => $document->getCours()->getId()]);
        }

        $qcmData = $mistralService->generateQcmFromText($text, $nbQuestions, $type);

        if (empty($qcmData)) {
            $this->addFlash('danger', 'L\'IA a renvoyé des données vides.');
            return $this->redirectToRoute('app_cours_show', ['id' => $document->getCours()->getId()]);
        }

        $typeLabel = ($type === 'vrai_faux') ? 'Vrai/Faux' : 'QCM';

        $qcm = new Qcm();
        $qcm->setTitle("$typeLabel IA ($nbQuestions q.) : " . $document->getTitle());
        $qcm->setCours($document->getCours());

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
        $this->addFlash('success', 'QCM généré avec succès !');
        return $this->redirectToRoute('app_cours_show', ['id' => $document->getCours()->getId()]);
    }
}