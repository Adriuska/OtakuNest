<?php

namespace App\Repository;

use App\Entity\Anime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Anime>
 */
class AnimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anime::class);
    }

    public function findFeatured(int $limit = 6): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.year', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecent(int $limit = 12): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPopular(int $limit = 12): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.rating', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByGenre(string $genreSlug, int $limit = 20): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.genres', 'g')
            ->where('g.slug = :slug')
            ->setParameter('slug', $genreSlug)
            ->orderBy('a.title', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByYear(int $year): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.year = :year')
            ->setParameter('year', $year)
            ->orderBy('a.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAiring(): array
    {
        return $this->findByStatus('airing');
    }

    public function findFinished(): array
    {
        return $this->findByStatus('finished');
    }
}
