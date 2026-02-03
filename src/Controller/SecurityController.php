<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[\Symfony\Component\Routing\Annotation\Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si déjà connecté, rediriger selon le rôle
        if ($this->getUser()) {
            return $this->redirectBasedOnRole();
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/login-redirect', name: 'login_redirect')]
    public function loginRedirect(): Response
    {
        return $this->redirectBasedOnRole();
    }

    private function redirectBasedOnRole(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur a le rôle ROLE_TEACHER
        if (in_array('ROLE_TEACHER', $user->getRoles())) {
            return $this->redirectToRoute('teacher_dashboard');
        }

        // Sinon, rediriger vers l'application React (étudiant)
        return $this->redirectToRoute('student_app');
    }
}
