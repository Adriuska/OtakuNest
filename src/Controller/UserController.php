<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->getString('email');
            $username = $request->request->getString('username');
            $password = $request->request->getString('password');
            $confirmPassword = $request->request->getString('confirmPassword');
            $firstName = $request->request->getString('firstName');
            $lastName = $request->request->getString('lastName');

            // Validations
            $errors = [];

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email inválido';
            }

            if ($userRepository->findOneBy(['email' => $email])) {
                $errors[] = 'Este email ya está registrado';
            }

            if (!$username || strlen($username) < 3) {
                $errors[] = 'El nombre de usuario debe tener al menos 3 caracteres';
            }

            if ($userRepository->findOneBy(['username' => $username])) {
                $errors[] = 'Este nombre de usuario ya existe';
            }

            if (!$password || strlen($password) < 6) {
                $errors[] = 'La contraseña debe tener al menos 6 caracteres';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Las contraseñas no coinciden';
            }

            if (empty($errors)) {
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($username);
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setRoles(['ROLE_USER']);

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Usuario registrado correctamente');
                return $this->redirectToRoute('app_home');
            }

            return $this->render('user/register.html.twig', [
                'errors' => $errors,
            ]);
        }

        return $this->render('user/register.html.twig');
    }

    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_register');
        }

        if ($request->isMethod('POST')) {
            $firstName = $request->request->getString('firstName');
            $lastName = $request->request->getString('lastName');
            $newPassword = $request->request->getString('newPassword');

            $user->setFirstName($firstName);
            $user->setLastName($lastName);

            if ($newPassword && strlen($newPassword) >= 6) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            $user->touch();
            $em->flush();

            $this->addFlash('success', 'Perfil actualizado correctamente');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_register');
        }

        return $this->render('user/dashboard.html.twig', [
            'user' => $user,
            'libraries' => $user->getLibraries(),
            'favorites' => $user->getFavorites(),
        ]);
    }
}
