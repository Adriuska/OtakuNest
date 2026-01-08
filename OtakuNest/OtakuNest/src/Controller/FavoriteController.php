<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\Favorite;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/favorite')]
class FavoriteController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'app_favorite_toggle', methods: ['POST'])]
    public function toggle(Anime $anime, FavoriteRepository $favoriteRepository, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Debes iniciar sesión'], 401);
        }

        $favorite = $favoriteRepository->findOneBy([
            'user' => $user,
            'anime' => $anime,
        ]);

        if ($favorite) {
            $em->remove($favorite);
            $em->flush();
            return $this->json(['status' => 'removed']);
        } else {
            $favorite = new Favorite();
            $favorite->setUser($user);
            $favorite->setAnime($anime);

            $em->persist($favorite);
            $em->flush();
            return $this->json(['status' => 'added']);
        }
    }

    #[Route('', name: 'app_favorites', methods: ['GET'])]
    public function list(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $favorites = $user->getFavorites();

        return $this->render('favorite/list.html.twig', [
            'favorites' => $favorites,
        ]);
    }

    #[Route('/{id}', name: 'app_favorite_delete', methods: ['DELETE'])]
    public function delete(Favorite $favorite, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || $favorite->getUser() !== $user) {
            return $this->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        try {
            $em->remove($favorite);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Favorito eliminado'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
