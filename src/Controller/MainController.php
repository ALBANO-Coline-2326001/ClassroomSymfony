<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; // Vérifie bien que c'est 'Attribute'
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('main/index.html.twig');
    }

    #[Route('/teacher/space', name: 'app_teacher_space')]
    #[IsGranted('ROLE_TEACHER')] // Seuls les profs accèdent à cet espace
    public function teacherSpace(): Response
    {
        // On pointe vers le nouveau nom de fichier
        return $this->render('main/teacher_space.html.twig');
    }
}
