<?php

namespace App\Controller;

use App\Entity\Library;
use App\Entity\LibraryItem;
use App\Repository\AnimeRepository;
use App\Repository\LibraryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/library')]
class LibraryController extends AbstractController
{
    #[Route('', name: 'app_library_list', methods: ['GET'])]
    public function list(LibraryRepository $libraryRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $libraries = $libraryRepository->findBy(['owner' => $user], ['createdAt' => 'DESC']);

        return $this->render('library/list.html.twig', [
            'libraries' => $libraries,
        ]);
    }

    #[Route('/create', name: 'app_library_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $name = $request->request->getString('name');
            $description = $request->request->getString('description');
            $visibility = $request->request->getString('visibility', Library::VISIBILITY_PRIVATE);

            if (!$name) {
                $this->addFlash('error', 'El nombre de la biblioteca es requerido');
                return $this->redirectToRoute('app_library_create');
            }

            $library = new Library();
            $library->setName($name);
            $library->setDescription($description);
            $library->setVisibility($visibility);
            $library->setOwner($user);

            $em->persist($library);
            $em->flush();

            $this->addFlash('success', 'Biblioteca creada correctamente');
            return $this->redirectToRoute('app_library_show', ['id' => $library->getId()]);
        }

        return $this->render('library/create.html.twig');
    }

    #[Route('/{id}', name: 'app_library_show', methods: ['GET'])]
    public function show(Library $library): Response
    {
        $user = $this->getUser();

        if ($user !== $library->getOwner() && $library->getVisibility() !== Library::VISIBILITY_PUBLIC) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('library/show.html.twig', [
            'library' => $library,
            'items' => $library->getItems(),
        ]);
    }

    #[Route('/{id}/add-anime', name: 'app_library_add_anime', methods: ['POST'])]
    public function addAnime(Library $library, Request $request, EntityManagerInterface $em, AnimeRepository $animeRepository): Response
    {
        $user = $this->getUser();

        if ($user !== $library->getOwner()) {
            throw $this->createAccessDeniedException();
        }

        $animeId = $request->request->get('animeId');
        $status = $request->request->getString('status', LibraryItem::STATUS_PLANNED);

        if (!$animeId) {
            $this->addFlash('error', 'Debes seleccionar un anime.');
            return $this->redirectToRoute('app_library_show', ['id' => $library->getId()]);
        }

        $anime = $animeRepository->find($animeId);
        if (!$anime) {
            $this->addFlash('error', 'El anime no existe.');
            return $this->redirectToRoute('app_library_show', ['id' => $library->getId()]);
        }

        // Evitar duplicados por la restricción única (library_id, anime_id)
        foreach ($library->getItems() as $item) {
            if ($item->getAnime() === $anime) {
                $this->addFlash('info', 'Este anime ya está en la biblioteca.');
                return $this->redirectToRoute('app_library_show', ['id' => $library->getId()]);
            }
        }

        $item = new LibraryItem();
        $item->setLibrary($library);
        $item->setAnime($anime);
        $item->setStatus($status);

        $library->touch();

        $em->persist($item);
        $em->flush();

        $this->addFlash('success', 'Anime añadido a la biblioteca');
        return $this->redirectToRoute('app_library_show', ['id' => $library->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_library_delete', methods: ['POST'])]
    public function delete(Library $library, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($user !== $library->getOwner()) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($library);
        $em->flush();

        $this->addFlash('success', 'Biblioteca eliminada');
        return $this->redirectToRoute('app_library_list');
    }

    #[Route('/{id}/edit', name: 'app_library_edit', methods: ['GET', 'POST'])]
    public function edit(Library $library, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($user !== $library->getOwner()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $name = $request->request->getString('name');
            $description = $request->request->getString('description');
            $visibility = $request->request->getString('visibility', Library::VISIBILITY_PRIVATE);

            if (!$name) {
                $this->addFlash('error', 'El nombre de la biblioteca es requerido');
                return $this->redirectToRoute('app_library_edit', ['id' => $library->getId()]);
            }

            $library->setName($name);
            $library->setDescription($description);
            $library->setVisibility($visibility);
            $library->touch();

            $em->flush();

            $this->addFlash('success', 'Biblioteca actualizada correctamente');
            return $this->redirectToRoute('app_library_show', ['id' => $library->getId()]);
        }

        return $this->render('library/edit.html.twig', [
            'library' => $library,
        ]);
    }
}
