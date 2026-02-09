<?php

namespace App\Controller;

use App\Repository\CoursRepository;
use App\Repository\NoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/teacher')]
#[IsGranted('ROLE_TEACHER')]
class TeacherController extends AbstractController
{
    #[Route('/dashboard', name: 'teacher_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('teacher/dashboard.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/my-cours', name: 'teacher_cours')]
    public function myCours(CoursRepository $coursRepository): Response
    {
        $teacher = $this->getUser();
        $myCourses = $coursRepository->findBy(['teacher' => $teacher]);

        return $this->render('teacher/cours.html.twig', [
            'cours' => $myCourses,
        ]);
    }

    #[Route('/notes', name: 'teacher_notes')]
    public function notes(NoteRepository $noteRepository): Response
    {
        $notes = $noteRepository->findByTeacher($this->getUser());

        return $this->render('teacher/notes.html.twig', [
            'notes' => $notes,
        ]);
    }
}