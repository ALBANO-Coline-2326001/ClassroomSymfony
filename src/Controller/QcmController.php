<?php

namespace App\Controller;

use App\Repository\NoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QcmController extends AbstractController
{
    #[Route('/qcm', name: 'app_qcm')]
    public function index(NoteRepository $noteRepository): Response
    {
        $attempt = $noteRepository->findOneBy([], ['attemptedAt' => 'DESC']);

        return $this->render('qcm/index.html.twig', [
            'attempt' => $attempt
        ]);
    }
}
