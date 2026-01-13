<?php

namespace App\Controller;

use App\Entity\Library;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/user/libraries', name: 'app_api_user_libraries', methods: ['GET'])]
    public function getUserLibraries(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        try {
            $libraries = $em->getRepository(Library::class)->findBy(['owner' => $user]);

            $data = array_map(fn($lib) => [
                'id' => $lib->getId(),
                'name' => $lib->getName(),
                'items' => count($lib->getItems()),
            ], $libraries);

            return $this->json([
                'success' => true,
                'libraries' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
