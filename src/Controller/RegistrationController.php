<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $roleChoisi = $form->get('roles')->getData();

            if ($roleChoisi === 'ROLE_TEACHER' || $roleChoisi === 'ROLE_PROF') {
                $finalUser = new Teacher();
            } else {
                $finalUser = new Student();
            }

            $finalUser->setEmail($user->getEmail());
            $finalUser->setFirstName($user->getFirstName());
            $finalUser->setLastName($user->getLastName());
            $finalUser->setRoles([$roleChoisi]);

            /** @var string $plainPassword */
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $finalUser->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $role = $form->get('roles')->getData();
            $finalUser->setRoles([$role]);

            $entityManager->persist($finalUser);
            $entityManager->flush();

            return $security->login($finalUser, UserAuthenticator::class, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
