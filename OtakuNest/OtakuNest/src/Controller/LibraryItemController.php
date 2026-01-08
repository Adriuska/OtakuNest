<?php

namespace App\Controller;

use App\Entity\LibraryItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/library-item')]
class LibraryItemController extends AbstractController
{
    #[Route('/{id}', name: 'app_library_item_delete', methods: ['DELETE'])]
    public function delete(LibraryItem $item, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || $item->getLibrary()->getOwner() !== $user) {
            return $this->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        try {
            $em->remove($item);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Item eliminado de la biblioteca'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
