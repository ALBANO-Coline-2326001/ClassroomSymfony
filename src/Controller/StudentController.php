<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STUDENT')]
class StudentController extends AbstractController
{
    #[Route('/student/{reactRouting}', name: 'student_app', requirements: ['reactRouting' => '.*'], defaults: ['reactRouting' => null])]
    public function index(): Response
    {
        // Servir le build React
        $reactBuildPath = $this->getParameter('kernel.project_dir') . '/student-app/dist/index.html';

        if (!file_exists($reactBuildPath)) {
            throw $this->createNotFoundException('React app not built. Run: npm run build');
        }

        return new Response(file_get_contents($reactBuildPath));
    }
}
