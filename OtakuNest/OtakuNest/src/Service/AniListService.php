<?php

namespace App\Service;

use App\Entity\Anime;
use App\Entity\Episode;
use App\Entity\Genre;
use App\Repository\GenreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AniListService
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $em;
    private GenreRepository $genreRepository;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $em,
        GenreRepository $genreRepository
    ) {
        $this->httpClient = $httpClient;
        $this->em = $em;
        $this->genreRepository = $genreRepository;
    }

    /**
     * Buscar anime en AniList por nombre
     */
    public function searchAnime(string $title): ?array
    {
        $query = <<<'GRAPHQL'
        query ($search: String) {
            Media(search: $search, type: ANIME) {
                id
                title {
                    romaji
                    english
                }
                description
                coverImage {
                    large
                }
                genres
                seasonYear
                status
                startDate {
                    year
                    month
                    day
                }
                episodes
                averageScore
            }
        }
        GRAPHQL;

        try {
            $response = $this->httpClient->request('POST', 'https://graphql.anilist.co', [
                'json' => [
                    'query' => $query,
                    'variables' => ['search' => $title],
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['data']['Media'])) {
                return $data['data']['Media'];
            }
        } catch (\Exception $e) {
            // Log error
        }

        return null;
    }

    /**
     * Obtener episodios de un anime desde AniList
     */
    public function getEpisodes(int $anilistId): array
    {
        // Note: AniList no proporciona episodios directamente en su API GraphQL
        // Se puede integrar con otra fuente o tener un sistema manual
        return [];
    }

    /**
     * Sincronizar anime desde AniList
     */
    public function syncAnimeFromAniList(string $title, Anime $anime = null): Anime
    {
        $anilistData = $this->searchAnime($title);

        if (!$anilistData) {
            throw new \Exception('Anime no encontrado en AniList');
        }

        if (!$anime) {
            $anime = new Anime();
        }

        // Actualizar campos básicos
        $anime->setTitle($anilistData['title']['romaji'] ?? $anilistData['title']['english'] ?? $title);
        $anime->setDescription($anilistData['description'] ?? null);
        $anime->setCoverUrl($anilistData['coverImage']['large'] ?? null);
        $anime->setYear($anilistData['seasonYear'] ?? null);
        $anime->setStatus($this->mapStatus($anilistData['status'] ?? 'UNKNOWN'));
        $anime->setRating(($anilistData['averageScore'] ?? 0) / 10);
        $anime->setTotalEpisodes($anilistData['episodes'] ?? null);
        $anime->setExternalId((string)($anilistData['id'] ?? null));
        $anime->setLastSyncedAt(new \DateTimeImmutable());

        // Agregar géneros
        foreach ($anilistData['genres'] ?? [] as $genreName) {
            $genre = $this->genreRepository->findOneBy(['name' => $genreName]);
            if (!$genre) {
                $genre = new Genre();
                $genre->setName($genreName);
                $genre->setSlug(strtolower(str_replace(' ', '-', $genreName)));
                $this->em->persist($genre);
            }
            $anime->addGenre($genre);
        }

        // Generar slug si no existe
        if (!$anime->getSlug()) {
            $anime->setSlug(strtolower(str_replace(' ', '-', $anime->getTitle())));
        }

        return $anime;
    }

    /**
     * Mapear estado de AniList a nuestro sistema
     */
    private function mapStatus(string $anilistStatus): string
    {
        return match ($anilistStatus) {
            'FINISHED' => 'finished',
            'RELEASING' => 'airing',
            'NOT_YET_RELEASED' => 'announced',
            default => 'announced',
        };
    }
}
