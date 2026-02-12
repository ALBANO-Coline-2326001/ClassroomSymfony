<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Document;
use App\Entity\Video;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class CoursController extends AbstractController
{
    #[Route('/cours', name: 'app_cours')]
    public function index(CoursRepository $coursRepository): Response
    {
        return $this->render('teacher/cours.html.twig', [
            'cours' => $coursRepository->findAll(),
        ]);
    }

    #[Route('/cours/new', name: 'app_cours_new')]
    #[IsGranted('ROLE_TEACHER')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $cours = new Cours();
        $form = $this->createForm(CoursType::class, $cours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cours->setTeacher($this->getUser());

            /** @var UploadedFile[] $documentFiles */
            $documentFiles = $form->get('documentFiles')->getData();

            foreach ($documentFiles as $documentFile) {
                if ($documentFile) {
                    $originalFilename = pathinfo($documentFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$documentFile->guessExtension();

                    try {
                        $documentFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/assets/document',
                            $newFilename
                        );

                        // Création Entité Document
                        $document = new Document();
                        $document->setTitle($originalFilename);
                        $document->setPath($newFilename);
                        $document->setCours($cours);

                        $entityManager->persist($document);

                    } catch (FileException $e) {}
                }
            }

            /** @var UploadedFile[] $videoFiles */
            $videoFiles = $form->get('videoFiles')->getData();

            foreach ($videoFiles as $videoFile) {
                if ($videoFile) {
                    $originalFilename = pathinfo($videoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$videoFile->guessExtension();

                    try {
                        $videoFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/assets/video',
                            $newFilename
                        );
                    } catch (FileException $e) {
                    }
                    $video = new Video();
                    $video->setTitle($originalFilename);

                    $video->setUrl($newFilename);

                    $video->setDuration(0);
                    $video->setCours($cours);

                    $entityManager->persist($video);
                }
            }

            $entityManager->persist($cours);
            $entityManager->flush();

            $this->addFlash('success', 'Le cours et ses fichiers ont été ajoutés !');
            return $this->redirectToRoute('teacher_cours');
        }

        return $this->render('cours/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/cours/{id}', name: 'app_cours_show')]
    #[IsGranted('ROLE_USER')]
    public function show(Cours $cours, GeminiService $geminiService): Response
    {
        return $this->render('cours/show.html.twig', [
            'cours' => $cours,
            'hasGemini' => $geminiService->isConfigured(),
        ]);
    }
}
