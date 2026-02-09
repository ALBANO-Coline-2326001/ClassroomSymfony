<?php

namespace App\Controller\Api;

use App\Entity\Note;
use App\Entity\Student; // Important pour la sauvegarde
use App\Repository\UserRepository;
use App\Repository\CoursRepository;
use App\Repository\QcmRepository;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Préfixe global pour toutes les routes de ce contrôleur.
 * Cela évite tout conflit avec les routes générées automatiquement par API Platform.
 */
#[Route('/api/custom')]
class StudentApiController extends AbstractController
{
    /**
     * URL : GET /api/custom/courses
     */
    #[Route('/courses', name: 'api_custom_courses_list', methods: ['GET'])]
    public function listCourses(CoursRepository $coursRepository): JsonResponse
    {
        $courses = $coursRepository->findAll();
        $data = [];
        foreach ($courses as $cours) {
            $videos = [];
            foreach ($cours->getVideos() as $video) {
                $videos[] = ['id' => $video->getId(), 'title' => $video->getTitle(), 'url' => $video->getUrl()];
            }
            $documents = [];
            foreach ($cours->getDocuments() as $document) {
                $documents[] = ['id' => $document->getId(), 'title' => $document->getTitle(), 'download_url' => '/uploads/documents/' . $document->getPath()];
            }
            $data[] = [
                'id' => $cours->getId(),
                'title' => $cours->getTitle(),
                'contenu' => $cours->getContenu(),
                'teacher' => [
                    'first_name' => $cours->getTeacher() ? $cours->getTeacher()->getFirstName() : '',
                    'last_name' => $cours->getTeacher() ? $cours->getTeacher()->getLastName() : '',
                ],
                'videos' => $videos,
                'documents' => $documents,
            ];
        }
        return $this->json($data);
    }

    /**
     * URL : GET /api/custom/students/{id}
     */
    #[Route('/students/{id}', name: 'api_custom_student_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): JsonResponse
    {
        $student = $userRepository->find($id);
        if (!$student) return $this->json(['error' => 'Student not found'], 404);

        return $this->json([
            'id' => $student->getId(),
            'first_name' => $student->getFirstName(),
            'last_name' => $student->getLastName(),
            'email' => $student->getEmail(),
        ]);
    }

    /**
     * URL : GET /api/custom/students/{id}/qcm-results
     */
    #[Route('/students/{id}/qcm-results', name: 'api_custom_student_qcm_results', methods: ['GET'])]
    public function qcmResults(int $id, NoteRepository $noteRepository): JsonResponse
    {
        $notes = $noteRepository->findBy(['student' => $id], ['attemptedAt' => 'DESC']);
        $data = [];
        foreach ($notes as $note) {
            $qcm = $note->getQcm();
            $data[] = [
                'id' => $note->getId(),
                'qcm_title' => $qcm ? $qcm->getTitle() : 'QCM inconnu',
                'course_title' => ($qcm && $qcm->getCours()) ? $qcm->getCours()->getTitle() : '-',
                'score' => $note->getScore(),
                'total_questions' => $qcm ? count($qcm->getQuestions()) : 0,
                'date' => $note->getAttemptedAt()->format('d/m/Y H:i'),
            ];
        }
        return $this->json($data);
    }

    /**
     * URL : GET /api/custom/qcms/{id}
     * C'est ici qu'on force l'utilisation de la logique SQL pour is_correct
     */
    #[Route('/qcms/{id}', name: 'api_custom_qcm_show', methods: ['GET'])]
    public function showQCM(int $id, QcmRepository $qcmRepository, EntityManagerInterface $em): JsonResponse
    {
        $qcm = $qcmRepository->find($id);
        if (!$qcm) return $this->json(['error' => 'QCM non trouvé'], 404);

        $questionIds = [];
        foreach ($qcm->getQuestions() as $q) $questionIds[] = $q->getId();

        $correctnessMap = [];
        if (!empty($questionIds)) {
            $conn = $em->getConnection();
            $idsStr = implode(',', array_map('intval', $questionIds));

            try {
                $rows = $conn->fetchAllAssociative("SELECT id, is_correct FROM answer WHERE question_id IN ($idsStr)");
                foreach ($rows as $row) {
                    $correctnessMap[$row['id']] = (int)$row['is_correct'];
                }
            } catch (\Exception $e) { error_log("Erreur SQL: " . $e->getMessage()); }
        }

        $questions = [];
        foreach ($qcm->getQuestions() as $question) {
            $qText = method_exists($question, 'getEntitled') ? $question->getEntitled() :
                (method_exists($question, 'getText') ? $question->getText() : 'Question sans titre');

            $answers = [];
            foreach ($question->getAnswers() as $answer) {
                $aText = method_exists($answer, 'getText') ? $answer->getText() : 'Réponse sans texte';

                // On prend la valeur SQL brute (1 ou 0)
                $realIsCorrect = $correctnessMap[$answer->getId()] ?? 0;

                $answers[] = [
                    'id' => $answer->getId(),
                    'text' => $aText,
                    'is_correct' => ($realIsCorrect === 1), // Pour React (booléen)
                    'is_correct_int' => $realIsCorrect,     // Pour Debug (entier)
                ];
            }
            $questions[] = ['id' => $question->getId(), 'text' => $qText, 'answers' => $answers];
        }

        return $this->json(['id' => $qcm->getId(), 'title' => method_exists($qcm, 'getTitle') ? $qcm->getTitle() : 'QCM', 'questions' => $questions]);
    }

    /**
     * URL : POST /api/custom/students/{studentId}/qcms/{qcmId}/submit
     */
    #[Route('/students/{studentId}/qcms/{qcmId}/submit', name: 'api_custom_qcm_submit', methods: ['POST'])]
    public function submitQcm(int $studentId, int $qcmId, Request $request, EntityManagerInterface $em, QcmRepository $qcmRepo): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['score'])) return $this->json(['error' => 'Données manquantes'], 400);

        $student = $em->getRepository(Student::class)->find($studentId);
        $qcm = $qcmRepo->find($qcmId);

        if (!$student || !$qcm) return $this->json(['error' => 'Introuvable'], 404);

        try {
            $note = new Note();
            $note->setScore((int)$data['score']);
            $note->setAttemptedAt(new \DateTimeImmutable());
            $note->setStudent($student);
            $note->setQcm($qcm);
            $em->persist($note);
            $em->flush();
            return $this->json(['status' => 'success', 'id' => $note->getId()], 201);
        } catch (\Exception $e) { return $this->json(['error' => $e->getMessage()], 500); }
    }

    /**
     * URL : GET /api/custom/qcms
     */
    #[Route('/qcms', name: 'api_custom_qcm_list', methods: ['GET'])]
    public function listQCM(CoursRepository $coursRepository): JsonResponse
    {
        $courses = $coursRepository->findAll();
        $data = [];
        foreach ($courses as $cours) {
            $qcms = [];
            foreach ($cours->getQcms() as $qcm) {
                $qcms[] = [
                    'id' => $qcm->getId(),
                    'title' => method_exists($qcm, 'getTitle') ? $qcm->getTitle() : 'QCM',
                    'questions_count' => count($qcm->getQuestions()),
                ];
            }
            if (!empty($qcms)) $data[] = ['course_id' => $cours->getId(), 'course_title' => $cours->getTitle(), 'qcms' => $qcms];
        }
        return $this->json($data);
    }

    #[Route('/uploads/documents/{filename}', name: 'download_document', methods: ['GET'])]
    public function downloadDocument(string $filename): BinaryFileResponse {
        return $this->file($this->getParameter('kernel.project_dir') . '/public/upload/' . $filename);
    }

    #[Route('/uploads/videos/{filename}', name: 'get_video', methods: ['GET'])]
    public function getVideo(string $filename): BinaryFileResponse {
        $response = $this->file($this->getParameter('kernel.project_dir') . '/public/upload/' . $filename);
        $response->headers->set('Content-Type', 'video/mp4');
        return $response;
    }
}
