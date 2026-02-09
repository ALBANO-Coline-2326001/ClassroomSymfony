<?php

namespace App\Controller\Api;

use App\Entity\Note;
use App\Repository\UserRepository;
use App\Repository\CoursRepository;
use App\Repository\QcmRepository;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StudentApiController extends AbstractController
{
    /**
     * Liste tous les cours avec leurs vidéos et documents associés.
     */
    #[Route('/api/courses', name: 'api_courses_list', methods: ['GET'])]
    public function listCourses(CoursRepository $coursRepository): JsonResponse
    {
        $courses = $coursRepository->findAll();

        $data = [];
        foreach ($courses as $cours) {
            $teacher = $cours->getTeacher();

            $videos = [];
            foreach ($cours->getVideos() as $video) {
                $videos[] = [
                    'id' => $video->getId(),
                    'title' => $video->getTitle(),
                    'url' => $video->getUrl(),
                    'duration' => $video->getDuration(),
                ];
            }

            $documents = [];
            foreach ($cours->getDocuments() as $document) {
                $documents[] = [
                    'id' => $document->getId(),
                    'title' => $document->getTitle(),
                    'path' => $document->getPath(),
                    'download_url' => '/uploads/documents/' . $document->getPath(),
                ];
            }

            $data[] = [
                'id' => $cours->getId(),
                'title' => $cours->getTitle(),
                'contenu' => $cours->getContenu(),
                'teacher' => [
                    'id' => $teacher ? $teacher->getId() : null,
                    'first_name' => $teacher ? $teacher->getFirstName() : null,
                    'last_name' => $teacher ? $teacher->getLastName() : null,
                ],
                'videos' => $videos,
                'documents' => $documents,
            ];
        }

        return $this->json($data);
    }

    /**
     * Affiche les informations de base d'un étudiant.
     */
    #[Route('/api/students/{id}', name: 'api_student_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): JsonResponse
    {
        $student = $userRepository->find($id);

        if (!$student) {
            return $this->json(['error' => 'Student not found'], 404);
        }

        if (!in_array('ROLE_STUDENT', $student->getRoles(), true)) {
            return $this->json(['error' => 'User is not a student'], 403);
        }

        return $this->json([
            'id' => $student->getId(),
            'first_name' => $student->getFirstName(),
            'last_name' => $student->getLastName(),
            'email' => $student->getEmail(),
        ]);
    }

    /**
     * Récupère les résultats réels de l'étudiant depuis la table 'note'.
     */
    #[Route('/api/students/{id}/qcm-results', name: 'api_student_qcm_results', methods: ['GET'])]
    public function qcmResults(int $id, NoteRepository $noteRepository): JsonResponse
    {
        $notes = $noteRepository->findBy(['student' => $id], ['attempted_at' => 'DESC']);
        $data = [];
        foreach ($notes as $note) {
            $qcm = $note->getQcm();
            // Gestion sécurisée si le QCM ou le cours a été supprimé
            $cours = ($qcm && $qcm->getCours()) ? $qcm->getCours() : null;

            $data[] = [
                'id' => $note->getId(),
                'qcm_title' => $qcm ? $qcm->getTitle() : 'QCM inconnu',
                'course_title' => $cours ? $cours->getTitle() : 'Cours non spécifié',
                'score' => $note->getScore(),
                'total_questions' => $qcm ? count($qcm->getQuestions()) : 0,
                'date' => $note->getAttemptedAt()->format('d/m/Y à H:i'),
            ];
        }
        return $this->json($data);
    }

    #[Route('/api/students/{studentId}/qcms/{qcmId}/submit', name: 'api_qcm_submit', methods: ['POST'])]
    public function submitQcm(
        int $studentId,
        int $qcmId,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        QcmRepository $qcmRepo
    ): JsonResponse {
        // Décodage
        $data = json_decode($request->getContent(), true);

        // Si le JSON est invalide ou vide, on évite le crash PHP
        if (!$data || !isset($data['score'])) {
            return $this->json(['error' => 'Données manquantes (score)'], 400);
        }

        $student = $userRepo->find($studentId);
        $qcm = $qcmRepo->find($qcmId);

        if (!$student || !$qcm) {
            return $this->json(['error' => 'Étudiant ou QCM introuvable'], 404);
        }

        try {
            $note = new Note();
            $note->setScore((int)$data['score']);
            $note->setAttemptedAt(new \DateTime());
            $note->setStudent($student);
            $note->setQcm($qcm);

            $em->persist($note);
            $em->flush();

            return $this->json(['status' => 'success', 'id' => $note->getId()], 201);

        } catch (\Exception $e) {
            // Cela te permettra de voir la vraie erreur dans la console Network
            return $this->json([
                'error' => 'Erreur serveur lors de la sauvegarde',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste les QCM groupés par cours.
     */
    #[Route('/api/qcms', name: 'api_qcm_list', methods: ['GET'])]
    public function listQCM(CoursRepository $coursRepository): JsonResponse
    {
        $courses = $coursRepository->findAll();

        $data = [];
        foreach ($courses as $cours) {
            $qcms = [];
            foreach ($cours->getQcms() as $qcm) {
                $qcms[] = [
                    'id' => $qcm->getId(), // ID du QCM
                    'title' => $qcm->getTitle(), // Titre du QCM
                    'questions_count' => count($qcm->getQuestions()),
                ];
            }

            $data[] = [
                'course_id' => $cours->getId(),
                'course_title' => $cours->getTitle(),
                'qcms' => $qcms,
            ];
        }

        return $this->json($data);
    }

    /**
     * Affiche les détails d'un QCM (questions et réponses) pour la popup React.
     */
    #[Route('/api/qcms/{id}', name: 'api_qcm_show', methods: ['GET'])]
    public function showQCM(int $id, QcmRepository $qcmRepository): JsonResponse
    {
        $qcm = $qcmRepository->find($id);

        if (!$qcm) {
            return $this->json(['error' => 'QCM non trouvé'], 404);
        }

        $questions = [];
        foreach ($qcm->getQuestions() as $question) {
            $answers = [];
            foreach ($question->getAnswers() as $answer) {
                $answers[] = [
                    'id' => $answer->getId(), // ID de la réponse
                    'text' => $answer->getText(), // Texte de la réponse
                    'is_correct' => $answer->isCorrect(), // 1 si correct, 0 sinon
                ];
            }

            $questions[] = [
                'id' => $question->getId(), // ID de la question
                'text' => $question->getEntitled(), // Libellé de la question
                'answers' => $answers,
            ];
        }

        return $this->json([
            'id' => $qcm->getId(),
            'title' => $qcm->getTitle(),
            'questions' => $questions,
        ]);
    }

    /**
     * Gère le téléchargement des documents stockés sur le serveur.
     */
    #[Route('/uploads/documents/{filename}', name: 'download_document', methods: ['GET'])]
    public function downloadDocument(string $filename): BinaryFileResponse
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/upload/' . $filename;

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('Document introuvable : ' . $filename);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }

    /**
     * Permet la lecture en streaming des vidéos stockées sur le serveur.
     */
    #[Route('/uploads/videos/{filename}', name: 'get_video', methods: ['GET'])]
    public function getVideo(string $filename): BinaryFileResponse
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public/upload/' . $filename;

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('Vidéo introuvable : ' . $filename);
        }

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', 'video/mp4');

        return $response;
    }
}
