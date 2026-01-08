<?php

namespace App\Controller;

use App\Repository\AnimeRepository;
use App\Repository\UserRepository;
use App\Repository\FavoriteRepository;
use App\Repository\LibraryItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        AnimeRepository $animeRepository,
        UserRepository $userRepository,
        FavoriteRepository $favoriteRepository,
        LibraryItemRepository $libraryItemRepository
    ): Response {
        $featured = $animeRepository->findFeatured(6);
        $popular = $animeRepository->findPopular(6);
        $recent = $animeRepository->findRecent(6);

        // Contar usuarios totales en la BD
        $totalUsers = $userRepository->count([]);

        // Catálogo disponible mediante API
        $totalAnimes = 120;

        return $this->render('home/index.html.twig', [
            'featured' => $featured,
            'popular' => $popular,
            'recent' => $recent,
            'totalAnimes' => $totalAnimes,
            'totalUsers' => $totalUsers,
        ]);
    }
}
