<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class JikanService
{
    private const BASE_URL = 'https://api.jikan.moe/v4';

    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Buscar anime por nombre
     */
    public function searchAnime(string $title): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/anime', [
                'query' => ['query' => $title, 'limit' => 25],
                'timeout' => 15,
                'headers' => [
                    'User-Agent' => 'OtakuNest/1.0'
                ]
            ]);

            $data = $response->toArray();

            if (isset($data['data']) && is_array($data['data'])) {
                return array_map(fn($anime) => [
                    'id' => $anime['mal_id'] ?? null,
                    'title' => $anime['title'] ?? 'Sin título',
                    'title_english' => $anime['title_english'] ?? null,
                    'image' => $anime['images']['jpg']['large_image_url'] ?? $anime['images']['jpg']['image_url'] ?? null,
                    'description' => $anime['synopsis'] ?? '',
                    'genres' => array_map(fn($g) => $g['name'], $anime['genres'] ?? []),
                    'year' => $anime['year'] ?? null,
                    'status' => $anime['status'] ?? 'Unknown',
                    'episodes' => $anime['episodes'] ?? 0,
                    'rating' => $anime['score'] ?? 0,
                    'rating_count' => $anime['scored_by'] ?? 0,
                    'type' => $anime['type'] ?? 'Unknown',
                ], $data['data']);
            }
        } catch (\Exception $e) {
            error_log('Jikan API Error: ' . $e->getMessage());
            return [];
        }

        return [];
    }

    /**
     * Obtener detalle completo de un anime
     */
    public function getAnimeDetail(int $malId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/anime/' . $malId);

            $data = $response->toArray();

            if (isset($data['data'])) {
                $anime = $data['data'];
                return [
                    'id' => $anime['mal_id'] ?? null,
                    'title' => $anime['title'] ?? 'Sin título',
                    'title_english' => $anime['title_english'] ?? null,
                    'title_japanese' => $anime['title_japanese'] ?? null,
                    'image' => $anime['images']['jpg']['image_url'] ?? null,
                    'image_large' => $anime['images']['jpg']['large_image_url'] ?? null,
                    'description' => $anime['synopsis'] ?? '',
                    'genres' => array_map(fn($g) => $g['name'], $anime['genres'] ?? []),
                    'year' => $anime['year'] ?? null,
                    'status' => $anime['status'] ?? 'Unknown',
                    'episodes' => $anime['episodes'] ?? 0,
                    'rating' => $anime['score'] ?? 0,
                    'rating_count' => $anime['scored_by'] ?? 0,
                    'type' => $anime['type'] ?? 'Unknown',
                    'aired_from' => $anime['aired']['from'] ?? null,
                    'aired_to' => $anime['aired']['to'] ?? null,
                    'studios' => array_map(fn($s) => $s['name'], $anime['studios'] ?? []),
                ];
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Obtener animes por género
     */
    public function getAnimeByGenre(int $genreId, int $page = 1): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/genres/anime/' . $genreId, [
                'query' => ['page' => $page, 'limit' => 25],
            ]);

            $data = $response->toArray();

            if (isset($data['data']) && is_array($data['data'])) {
                return array_map(fn($anime) => [
                    'id' => $anime['mal_id'] ?? null,
                    'title' => $anime['title'] ?? 'Sin título',
                    'image' => $anime['images']['jpg']['large_image_url'] ?? $anime['images']['jpg']['image_url'] ?? null,
                    'rating' => $anime['score'] ?? 0,
                ], $data['data']);
            }
        } catch (\Exception $e) {
            return [];
        }

        return [];
    }

    /**
     * Obtener animes más populares
     */
    public function getPopularAnime(int $page = 1): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/top/anime', [
                'query' => ['page' => $page, 'limit' => 25, 'type' => 'tv'],
            ]);

            $data = $response->toArray();

            if (isset($data['data']) && is_array($data['data'])) {
                return array_map(fn($anime) => [
                    'id' => $anime['mal_id'] ?? null,
                    'title' => $anime['title'] ?? 'Sin título',
                    'title_english' => $anime['title_english'] ?? null,
                    'image' => $anime['images']['jpg']['large_image_url'] ?? $anime['images']['jpg']['image_url'] ?? null,
                    'description' => $anime['synopsis'] ?? '',
                    'genres' => array_map(fn($g) => $g['name'], $anime['genres'] ?? []),
                    'year' => $anime['year'] ?? null,
                    'status' => $anime['status'] ?? 'Unknown',
                    'episodes' => $anime['episodes'] ?? 0,
                    'rating' => $anime['score'] ?? 0,
                    'rating_count' => $anime['scored_by'] ?? 0,
                    'type' => $anime['type'] ?? 'Unknown',
                ], $data['data']);
            }
        } catch (\Exception $e) {
            return [];
        }

        return [];
    }

    /**
     * Obtener animes en emisión (airing)
     */
    public function getAiringAnime(int $page = 1): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/seasons/now', [
                'query' => ['page' => $page, 'limit' => 25],
            ]);

            $data = $response->toArray();

            if (isset($data['data']) && is_array($data['data'])) {
                return array_map(fn($anime) => [
                    'id' => $anime['mal_id'] ?? null,
                    'title' => $anime['title'] ?? 'Sin título',
                    'image' => $anime['images']['jpg']['large_image_url'] ?? $anime['images']['jpg']['image_url'] ?? null,
                    'rating' => $anime['score'] ?? 0,
                    'episodes' => $anime['episodes'] ?? 0,
                ], $data['data']);
            }
        } catch (\Exception $e) {
            return [];
        }

        return [];
    }
}
