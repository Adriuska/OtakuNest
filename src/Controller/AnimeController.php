<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Entity\Favorite;
use App\Entity\Genre;
use App\Entity\Library;
use App\Entity\LibraryItem;
use App\Repository\AnimeRepository;
use App\Repository\GenreRepository;
use App\Service\JikanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/anime')]
class AnimeController extends AbstractController
{
    #[Route('', name: 'app_anime_list', methods: ['GET'])]
    public function list(Request $request, JikanService $jikanService, GenreRepository $genreRepo): Response
    {
        $search = $request->query->getString('search', '');
        $page = $request->query->getInt('page', 1);

        $animes = [];

        // Si hay búsqueda, usar Jikan API
        if ($search) {
            $animes = $jikanService->searchAnime($search);
        } else {
            // Si no hay búsqueda, cargar animes populares
            $animes = $jikanService->getPopularAnime($page);
        }

        // Convertir a array con estructura uniforme
        $animeData = array_map(fn($anime) => [
            'id' => $anime['id'] ?? null,
            'title' => $anime['title'] ?? 'Sin título',
            'image' => $anime['image'] ?? null,
            'rating' => $anime['rating'] ?? 0,
            'year' => $anime['year'] ?? null,
            'type' => $anime['type'] ?? 'Unknown',
            'status' => $anime['status'] ?? 'Unknown',
            'episodes' => $anime['episodes'] ?? 0,
            'genres' => $anime['genres'] ?? [],
        ], $animes);

        // Obtener géneros disponibles desde la BD; si no hay, construir desde los animes cargados
        $genreEntities = $genreRepo->findBy([], ['name' => 'ASC']);
        $availableGenres = array_map(fn($g) => $g->getName(), $genreEntities);

        if (empty($availableGenres)) {
            $set = [];
            foreach ($animeData as $a) {
                foreach ($a['genres'] as $g) {
                    $set[$g] = true;
                }
            }
            $availableGenres = array_values(array_keys($set));
            sort($availableGenres);
        }

        return $this->render('anime/list.html.twig', [
            'animes' => $animeData,
            'search' => $search,
            'page' => $page,
            'availableGenres' => $availableGenres,
        ]);
    }

    #[Route('/api/health', name: 'app_api_health', methods: ['GET'])]
    public function apiHealth(JikanService $jikanService): Response
    {
        try {
            $test = $jikanService->searchAnime('test');
            return $this->json([
                'status' => 'ok',
                'message' => 'OtakuNest API está funcionando correctamente',
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                'jikan_status' => 'connected',
                'test_results' => count($test) . ' resultados'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Error en la API',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/search', name: 'app_anime_search_api', methods: ['GET'])]
    public function searchApi(Request $request, JikanService $jikanService): Response
    {
        $q = $request->query->getString('q', '');

        if (strlen($q) < 2) {
            return $this->json([
                'error' => 'La búsqueda debe tener al menos 2 caracteres',
                'results' => []
            ]);
        }

        $animes = $jikanService->searchAnime($q);

        $data = array_map(fn($anime) => [
            'id' => $anime['id'],
            'title' => strtoupper($anime['title']),
            'title_english' => $anime['title_english'] ? strtoupper($anime['title_english']) : null,
            'image' => $anime['image'],
            'rating' => $anime['rating'],
            'year' => $anime['year'],
            'status' => $anime['status'],
            'type' => $anime['type'],
            'episodes' => $anime['episodes'],
            'genres' => $anime['genres'],
        ], $animes);

        return $this->json([
            'success' => true,
            'results' => $data,
            'total' => count($data)
        ]);
    }

    #[Route('/api/add-favorite', name: 'app_anime_add_favorite', methods: ['POST'])]
    public function addFavorite(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['anime_id']) || !isset($data['title'])) {
            return $this->json(['success' => false, 'message' => 'Datos inválidos'], 400);
        }

        try {
            // Crear favorito
            $favorite = new Favorite();
            $favorite->setUser($user);
            $favorite->setMalId((int)$data['anime_id']);
            $favorite->setTitle($data['title']);
            $favorite->setImage($data['image'] ?? null);
            $favorite->setAddedAt(new \DateTimeImmutable());

            $em->persist($favorite);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => $data['title'] . ' añadido a favoritos'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/add-library', name: 'app_anime_add_library', methods: ['POST'])]
    public function addLibrary(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['anime_id']) || !isset($data['title'])) {
            return $this->json(['success' => false, 'message' => 'Datos inválidos'], 400);
        }

        try {
            $library = null;

            // Si viene library_id, usarla; si no, obtener o crear la primera
            if (isset($data['library_id'])) {
                $library = $em->getRepository(Library::class)->find($data['library_id']);

                // Verificar que la biblioteca pertenezca al usuario
                if (!$library || $library->getOwner() !== $user) {
                    return $this->json(['success' => false, 'message' => 'Biblioteca no encontrada'], 404);
                }
            } else {
                // Obtener o crear biblioteca del usuario (por compatibilidad)
                $library = $em->getRepository(Library::class)->findOneBy(['owner' => $user]);

                if (!$library) {
                    $library = new Library();
                    $library->setOwner($user);
                    $library->setName('Mi Biblioteca');
                    $em->persist($library);
                    $em->flush();
                }
            }

            // Validar que no exista duplicado en la biblioteca
            $existingItem = $em->getRepository(LibraryItem::class)->findOneBy([
                'library' => $library,
                'malId' => (int)$data['anime_id']
            ]);

            if ($existingItem) {
                return $this->json([
                    'success' => false,
                    'message' => 'Este anime ya existe en ' . $library->getName()
                ], 409);
            }

            // Crear item en la biblioteca
            $item = new LibraryItem();
            $item->setLibrary($library);
            $item->setMalId((int)$data['anime_id']);
            $item->setTitle($data['title']);
            $item->setImage($data['image'] ?? null);
            $item->setStatus(LibraryItem::STATUS_PLANNED);
            $item->setAddedAt(new \DateTimeImmutable());

            $em->persist($item);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => $data['title'] . ' añadido a ' . $library->getName()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'app_anime_show_api', methods: ['GET'])]
    public function showApi(string $id, JikanService $jikanService): Response
    {
        $animeId = (int)$id;
        $anime = $jikanService->getAnimeDetail($animeId);

        if (!$anime) {
            return $this->json(['error' => 'Anime no encontrado'], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $anime
        ]);
    }
}
