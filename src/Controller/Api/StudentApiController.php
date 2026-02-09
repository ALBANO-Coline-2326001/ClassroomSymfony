<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Repository\CoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StudentApiController extends AbstractController
{
    #[Route('/api/courses', name: 'api_courses_list', methods: ['GET'])]
    public function listCourses(CoursRepository $coursRepository): JsonResponse
    {
        $courses = $coursRepository->findAll();

        $data = [];
        foreach ($courses as $cours) {
            $teacher = $cours->getTeacher();

            // Récupérer les vidéos
            $videos = [];
            foreach ($cours->getVideos() as $video) {
                $videos[] = [
                    'id' => $video->getId(),
                    'title' => $video->getTitle(),
                    'url' => $video->getUrl(), // Peut être une URL YouTube ou un chemin local
                    'duration' => $video->getDuration(),
                ];
            }

            // Récupérer les documents
            $documents = [];
            foreach ($cours->getDocuments() as $document) {
                $documents[] = [
                    'id' => $document->getId(),
                    'title' => $document->getTitle(),
                    'path' => $document->getPath(), // Le nom du fichier
                    'download_url' => '/uploads/documents/' . $document->getPath(), // URL de téléchargement
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
    #[Route('/api/students/{id}', name: 'api_student_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): JsonResponse
    {
        $student = $userRepository->find($id);

        if (!$student) {
            return $this->json(['error' => 'Student not found'], 404);
        }

        // Vérifier que c'est bien un étudiant
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

    #[Route('/api/students/{id}/courses', name: 'api_student_courses', methods: ['GET'])]
    public function courses(int $id, UserRepository $userRepository): JsonResponse
    {
        $student = $userRepository->find($id);

        if (!$student) {
            return $this->json(['error' => 'Student not found'], 404);
        }

        // TODO: Récupérer les cours de l'étudiant
        // Pour l'instant, retourner un tableau vide
        return $this->json([]);
    }

    #[Route('/api/students/{id}/qcm-results', name: 'api_student_qcm_results', methods: ['GET'])]
    public function qcmResults(int $id, UserRepository $userRepository): JsonResponse
    {
        $student = $userRepository->find($id);

        if (!$student) {
            return $this->json(['error' => 'Student not found'], 404);
        }

        // TODO: Récupérer les résultats QCM de l'étudiant
        // Pour l'instant, retourner un tableau vide
        return $this->json([]);
    }

    #[Route('/uploads/documents/{filename}', name: 'download_document', methods: ['GET'])]
    public function downloadDocument(string $filename): BinaryFileResponse
    {
        // Les documents sont dans public/upload
        $filePath = $this->getParameter('kernel.project_dir') . '/public/upload/' . $filename;

        // Si toujours pas trouvé, erreur
        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('Document introuvable: ' . $filename);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        return $response;
    }

    #[Route('/uploads/videos/{filename}', name: 'get_video', methods: ['GET'])]
    public function getVideo(string $filename): BinaryFileResponse
    {
        // Les vidéos sont dans public/upload
        $filePath = $this->getParameter('kernel.project_dir') . '/public/upload/' . $filename;

        // Si toujours pas trouvé, erreur
        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('Vidéo introuvable: ' . $filename);
        }

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', 'video/mp4');

        return $response;
    }
}
