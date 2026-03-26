<?php

namespace App\Controller\Guest;

use App\Entity\Annonce;
use App\Form\AnnonceTypeForm;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnnonceController extends AbstractController
{
    #[Route('/Guest/annonces', name: 'guest-annonces', methods: ['GET'])]
    public function listAnnonces(Request $request, AnnonceRepository $annonceRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 6;
        $total = $annonceRepository->countTotal();
        $totalPages = max(1, (int) ceil($total / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $annonces = $annonceRepository->findPaginated($page, $limit);


        return $this->render('guest/annonces/annonce-list.html.twig', [
            'annonces' => $annonces,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    #[Route('/Guest/annonces/create', name: 'guest-annonce-create', methods: ['GET', 'POST'])]
    public function createAnnonce(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $annonce = new Annonce();
        $form = $this->createForm(AnnonceTypeForm::class, $annonce);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $annonce->setSender($this->getUser());
            $annonce->setCreatedAt(new \DateTimeImmutable());

            // Gestion de l'upload d'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                try {
                    $extension = $this->validateImageUpload($imageFile);
                    $newFilename = uniqid() . '.' . $extension;
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $annonce->setImage($newFilename);
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->render('guest/annonces/annonce-create.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }

            $entityManager->persist($annonce);
            $entityManager->flush();

            $this->addFlash('success', 'Annonce créée avec succès !');
            return $this->redirectToRoute('guest-annonces');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire contient des erreurs, veuillez vérifier les champs.');
        }

        return $this->render('guest/annonces/annonce-create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/Guest/annonces/{id}', name: 'guest-annonce-show', methods: ['GET'])]
    public function showAnnonce(int $id, AnnonceRepository $annonceRepository): Response
    {
        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            $this->addFlash('error', 'Annonce introuvable');
            return $this->redirectToRoute('guest-annonces');
        }

        return $this->render('guest/annonces/annonce-show.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/Guest/annonces/{id}/update', name: 'guest-annonce-update', methods: ['GET', 'POST'])]
    public function updateAnnonce(int $id, Request $request, AnnonceRepository $annonceRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            $this->addFlash('error', 'Annonce introuvable');
            return $this->redirectToRoute('guest-annonces');
        }

        if ($annonce->getSender() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres annonces.');
            return $this->redirectToRoute('guest-annonces');
        }

        $form = $this->createForm(AnnonceTypeForm::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                try {
                    $extension = $this->validateImageUpload($imageFile);
                    $oldImage = $annonce->getImage();
                    if ($oldImage) {
                        $oldImagePath = $this->getParameter('images_directory') . '/' . $oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $newFilename = uniqid() . '.' . $extension;
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $annonce->setImage($newFilename);
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->render('guest/annonces/annonce-update.html.twig', [
                        'form' => $form->createView(),
                        'annonce' => $annonce,
                    ]);
                }
            }
            $entityManager->flush();

            $this->addFlash('success', 'Annonce modifiée avec succès !');
            return $this->redirectToRoute('guest-annonces');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire contient des erreurs, veuillez vérifier les champs.');
        }

        return $this->render('guest/annonces/annonce-update.html.twig', [
            'form' => $form->createView(),
            'annonce' => $annonce,
        ]);
    }

    #[Route('/Guest/annonces/{id}/delete', name: 'guest-annonce-delete', methods: ['POST'])]
    public function deleteAnnonce(int $id, Request $request, AnnonceRepository $annonceRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('delete_annonce_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('guest-annonces');
        }

        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            $this->addFlash('error', 'Annonce introuvable');
            return $this->redirectToRoute('guest-annonces');
        }

        if ($annonce->getSender() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres annonces.');
            return $this->redirectToRoute('guest-annonces');
        }

        if ($annonce && $annonce->getImage()) { // Supprimer l'image associée
            $imagePath = $this->getParameter('images_directory') . '/' . $annonce->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        try {
            $entityManager->remove($annonce);
            $entityManager->flush();

            $this->addFlash('success', 'Annonce supprimée avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de supprimer l\'annonce');
        }

        return $this->redirectToRoute('guest-annonces');
    }

    private function validateImageUpload(UploadedFile $imageFile): string
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageFile->getMimeType(), $allowedMimes, true)) {
            throw new \InvalidArgumentException('Format d\'image non autorisé');
        }

        if ($imageFile->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Image trop volumineuse (max 5MB)');
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = $imageFile->guessExtension();
        if (!$extension || !in_array($extension, $allowedExtensions, true)) {
            throw new \InvalidArgumentException('Extension non autorisée');
        }

        return $extension;
    }
}
