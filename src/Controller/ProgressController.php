<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Progress;
use App\Entity\User;
use App\Repository\ProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/progress')]
class ProgressController extends AbstractController
{
    #[Route('/episode/{id}/toggle', name: 'app_progress_toggle_episode', methods: ['POST'])]
    public function toggleEpisode(
        Episode $episode,
        ProgressRepository $progressRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Debes iniciar sesión'], 401);
        }

        $progress = $progressRepository->findOneBy([
            'user' => $user,
            'episode' => $episode,
        ]);

        if (!$progress) {
            $progress = new Progress();
            $progress->setUser($user);
            $progress->setEpisode($episode);
        }

        $progress->setSeen(!$progress->isSeen());
        $em->persist($progress);
        $em->flush();

        return $this->json([
            'seen' => $progress->isSeen(),
            'episodeNumber' => $episode->getNumber(),
        ]);
    }

    #[Route('/stats', name: 'app_progress_stats', methods: ['GET'])]
    public function stats(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $progressRecords = $user->getProgressRecords();
        $totalSeen = count($progressRecords->filter(fn($p) => $p->isSeen()));
        $totalEpisodes = count($progressRecords);

        return $this->render('progress/stats.html.twig', [
            'totalSeen' => $totalSeen,
            'totalEpisodes' => $totalEpisodes,
            'progressRecords' => $progressRecords,
        ]);
    }
}
