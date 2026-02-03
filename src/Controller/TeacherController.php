<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TeacherController extends AbstractController
{
    #[Route('/teacher/dashboard', name: 'teacher_dashboard')]
    #[IsGranted('ROLE_TEACHER')]
    public function dashboard(): Response
    {
        return $this->render('teacher/dashboard.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
