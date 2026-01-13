<?php

namespace App\Repository;

use App\Entity\Progress;
use App\Entity\User;
use App\Entity\Anime;
use App\Entity\Episode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Progress>
 */
class ProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Progress::class);
    }

    public function findByUserAndAnime(User $user, Anime $anime): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.episode', 'e')
            ->where('p.user = :user')
            ->andWhere('e.anime = :anime')
            ->setParameter('user', $user)
            ->setParameter('anime', $anime)
            ->orderBy('e.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndEpisode(User $user, Episode $episode): ?Progress
    {
        return $this->findOneBy([
            'user' => $user,
            'episode' => $episode,
        ]);
    }

    public function getWatchedEpisodeCount(User $user): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.user = :user')
            ->andWhere('p.seen = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getRecentWatched(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.seen = true')
            ->setParameter('user', $user)
            ->orderBy('p.seenAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
