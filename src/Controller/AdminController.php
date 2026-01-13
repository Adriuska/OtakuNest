<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\Genre;
use App\Entity\User;
use App\Repository\AnimeRepository;
use App\Repository\UserRepository;
use App\Repository\GenreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        UserRepository $userRepository,
        AnimeRepository $animeRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $totalUsers = count($userRepository->findAll());
        $totalAnimes = 120; // Catálogo fijo disponible mediante API
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalAnimes' => $totalAnimes,
            'recentUsers' => $recentUsers,
        ]);
    }

    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findAll();

        return $this->render('admin/users/list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/{id}', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function editUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $email = $request->request->getString('email');
            $firstName = $request->request->getString('firstName');
            $lastName = $request->request->getString('lastName');
            $roles = $request->request->all()['roles'] ?? [];

            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setRoles($roles);
            $user->touch();

            $em->flush();
            $this->addFlash('success', 'Usuario actualizado');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/animes', name: 'app_admin_animes', methods: ['GET'])]
    public function animes(AnimeRepository $animeRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $animes = $animeRepository->findAll();

        return $this->render('admin/animes/list.html.twig', [
            'animes' => $animes,
        ]);
    }

    #[Route('/animes/create', name: 'app_admin_anime_create', methods: ['GET', 'POST'])]
    public function createAnime(
        Request $request,
        EntityManagerInterface $em,
        GenreRepository $genreRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        if ($request->isMethod('POST')) {
            $anime = new Anime();
            $title = $request->request->getString('title');
            $slug = strtolower(str_replace(' ', '-', $title));

            $anime->setTitle($title);
            $anime->setSlug($slug);
            $anime->setDescription($request->request->getString('description'));
            $anime->setCoverUrl($request->request->getString('coverUrl'));
            $anime->setYear((int)$request->request->getString('year'));
            $anime->setStatus($request->request->getString('status'));
            $anime->setRating((float)$request->request->getString('rating'));
            $anime->setTotalEpisodes((int)$request->request->getString('totalEpisodes'));

            $genreIds = $request->request->all()['genres'] ?? [];
            foreach ($genreIds as $genreId) {
                $genre = $genreRepository->find($genreId);
                if ($genre) {
                    $anime->addGenre($genre);
                }
            }

            $em->persist($anime);
            $em->flush();

            $this->addFlash('success', 'Anime creado');
            return $this->redirectToRoute('app_admin_animes');
        }

        $genres = $genreRepository->findAll();

        return $this->render('admin/animes/create.html.twig', [
            'genres' => $genres,
        ]);
    }

    #[Route('/animes/{id}/edit', name: 'app_admin_anime_edit', methods: ['GET', 'POST'])]
    public function editAnime(
        Anime $anime,
        Request $request,
        EntityManagerInterface $em,
        GenreRepository $genreRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        if ($request->isMethod('POST')) {
            $anime->setTitle($request->request->getString('title'));
            $anime->setDescription($request->request->getString('description'));
            $anime->setCoverUrl($request->request->getString('coverUrl'));
            $anime->setYear((int)$request->request->getString('year'));
            $anime->setStatus($request->request->getString('status'));
            $anime->setRating((float)$request->request->getString('rating'));
            $anime->setTotalEpisodes((int)$request->request->getString('totalEpisodes'));

            $genreIds = $request->request->all()['genres'] ?? [];
            $anime->getGenres()->clear();
            foreach ($genreIds as $genreId) {
                $genre = $genreRepository->find($genreId);
                if ($genre) {
                    $anime->addGenre($genre);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Anime actualizado');
            return $this->redirectToRoute('app_admin_animes');
        }

        $genres = $genreRepository->findAll();

        return $this->render('admin/animes/edit.html.twig', [
            'anime' => $anime,
            'genres' => $genres,
        ]);
    }

    #[Route('/animes/{id}/delete', name: 'app_admin_anime_delete', methods: ['POST'])]
    public function deleteAnime(Anime $anime, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $em->remove($anime);
        $em->flush();

        $this->addFlash('success', 'Anime eliminado');
        return $this->redirectToRoute('app_admin_animes');
    }
}
