<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\ProgressRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    private ProgressRepository $progressRepository;
    private FavoriteRepository $favoriteRepository;
    private EntityManagerInterface $em;

    public function __construct(
        ProgressRepository $progressRepository,
        FavoriteRepository $favoriteRepository,
        EntityManagerInterface $em
    ) {
        $this->progressRepository = $progressRepository;
        $this->favoriteRepository = $favoriteRepository;
        $this->em = $em;
    }

    /**
     * Obtener estadísticas completas de un usuario
     */
    public function getUserStats(User $user): array
    {
        $progressRecords = $this->progressRepository->findBy(['user' => $user]);
        $favorites = $this->favoriteRepository->findBy(['user' => $user]);

        $totalEpisodesSeen = count(array_filter($progressRecords, fn($p) => $p->isSeen()));
        $totalEpisodes = count($progressRecords);
        $totalFavorites = count($favorites);

        // Calcular géneros favoritos
        $favoriteGenres = $this->calculateFavoriteGenres($favorites);

        // Calcular animes en progreso
        $libraryStats = $this->calculateLibraryStats($user);

        return [
            'totalEpisodesSeen' => $totalEpisodesSeen,
            'totalEpisodes' => $totalEpisodes,
            'totalFavorites' => $totalFavorites,
            'totalAnimes' => $libraryStats['totalAnimes'],
            'completedAnimes' => $libraryStats['completedAnimes'],
            'watchingAnimes' => $libraryStats['watchingAnimes'],
            'watchProgress' => $totalEpisodes > 0 ? round(($totalEpisodesSeen / $totalEpisodes) * 100, 2) : 0,
            'favoriteGenres' => array_slice($favoriteGenres, 0, 5, true),
        ];
    }

    /**
     * Obtener próximos episodios para un usuario
     */
    public function getUpcomingEpisodes(User $user, int $days = 7): array
    {
        $libraries = $user->getLibraries();
        $upcomingEpisodes = [];

        foreach ($libraries as $library) {
            foreach ($library->getItems() as $item) {
                $anime = $item->getAnime();
                foreach ($anime->getEpisodes() as $episode) {
                    if ($episode->getAirDate()) {
                        $airDate = \DateTimeImmutable::createFromInterface($episode->getAirDate());
                        $now = new \DateTimeImmutable();
                        $futureDate = $now->add(new \DateInterval('P' . $days . 'D'));

                        if ($airDate > $now && $airDate <= $futureDate) {
                            $upcomingEpisodes[] = [
                                'episode' => $episode,
                                'anime' => $anime,
                                'airDate' => $episode->getAirDate(),
                            ];
                        }
                    }
                }
            }
        }

        // Ordenar por fecha
        usort($upcomingEpisodes, fn($a, $b) => $a['airDate'] <=> $b['airDate']);

        return array_slice($upcomingEpisodes, 0, 10);
    }

    /**
     * Obtener progreso detallado de series por usuario
     */
    public function getSeriesProgress(User $user): array
    {
        $progress = [];

        foreach ($user->getLibraries() as $library) {
            foreach ($library->getItems() as $item) {
                $anime = $item->getAnime();
                $progressRecords = $this->progressRepository->findBy([
                    'user' => $user,
                    'anime' => $anime,
                ]);

                $episodesSeen = count(array_filter($progressRecords, fn($p) => $p->isSeen()));
                $totalEpisodes = $anime->getTotalEpisodes() ?? 0;
                $progressPercent = $totalEpisodes > 0
                    ? round(($episodesSeen / $totalEpisodes) * 100, 2)
                    : 0;

                $progress[] = [
                    'anime' => $anime,
                    'episodesSeen' => $episodesSeen,
                    'totalEpisodes' => $totalEpisodes,
                    'progress' => $progressPercent,
                    'status' => $item->getStatus(),
                    'isCompleted' => $progressPercent >= 100,
                ];
            }
        }

        // Ordenar por progreso descendente
        usort($progress, fn($a, $b) => $b['progress'] <=> $a['progress']);

        return $progress;
    }

    /**
     * Obtener estadísticas por género
     */
    public function getGenreStatistics(User $user): array
    {
        $genres = [];
        $favorites = $this->favoriteRepository->findBy(['user' => $user]);

        foreach ($favorites as $favorite) {
            foreach ($favorite->getAnime()->getGenres() as $genre) {
                $genreName = $genre->getName();
                if (!isset($genres[$genreName])) {
                    $genres[$genreName] = [
                        'name' => $genreName,
                        'count' => 0,
                        'rating' => 0,
                    ];
                }
                $genres[$genreName]['count']++;
                $genres[$genreName]['rating'] += $favorite->getAnime()->getRating();
            }
        }

        // Calcular rating promedio y ordenar
        foreach ($genres as &$genre) {
            $genre['rating'] = $genre['count'] > 0
                ? round($genre['rating'] / $genre['count'], 2)
                : 0;
        }

        usort($genres, fn($a, $b) => $b['count'] <=> $a['count']);

        return $genres;
    }

    /**
     * Calcular géneros favoritos de forma optimizada
     */
    private function calculateFavoriteGenres(array $favorites): array
    {
        $favoriteGenres = [];

        foreach ($favorites as $favorite) {
            foreach ($favorite->getAnime()->getGenres() as $genre) {
                $genreName = $genre->getName();
                $favoriteGenres[$genreName] = ($favoriteGenres[$genreName] ?? 0) + 1;
            }
        }

        arsort($favoriteGenres);

        return $favoriteGenres;
    }

    /**
     * Calcular estadísticas de la biblioteca
     */
    private function calculateLibraryStats(User $user): array
    {
        $totalAnimes = 0;
        $completedAnimes = 0;
        $watchingAnimes = 0;

        foreach ($user->getLibraries() as $library) {
            foreach ($library->getItems() as $item) {
                $totalAnimes++;

                if ('completed' === $item->getStatus()) {
                    $completedAnimes++;
                } elseif ('watching' === $item->getStatus()) {
                    $watchingAnimes++;
                }
            }
        }

        return [
            'totalAnimes' => $totalAnimes,
            'completedAnimes' => $completedAnimes,
            'watchingAnimes' => $watchingAnimes,
        ];
    }
}
