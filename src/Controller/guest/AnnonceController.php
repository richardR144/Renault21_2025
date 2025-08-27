<?php

namespace App\Controller\Guest;

use App\Entity\Annonce;
use App\Form\AnnonceTypeForm;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnnonceController extends AbstractController
{
    #[Route('/Guest/annonces', name: 'guest-annonces', methods: ['GET'])]
    public function listAnnonces(AnnonceRepository $annonceRepository): Response
    {
        $annonces = $annonceRepository->findBy([], ['createdAt' => 'DESC']);


        return $this->render('guest/annonces/annonce-list.html.twig', [
            'annonces' => $annonces,
        ]);
    }

    #[Route('/Guest/annonces/create', name: 'guest-annonce-create', methods: ['GET', 'POST'])]
    public function createAnnonce(Request $request, EntityManagerInterface $entityManager): Response
    {
        $annonce = new Annonce();
        $form = $this->createForm(AnnonceTypeForm::class, $annonce);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $annonce->setSender($this->getUser());
            $annonce->setCreatedAt(new \DateTimeImmutable());

            // Gestion de l'upload d'image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
                $annonce->setImage($newFilename);
            }

            $entityManager->persist($annonce);
            $entityManager->flush();

            $this->addFlash('success', 'Annonce créée avec succès !');
            return $this->redirectToRoute('guest-annonces');
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
                $oldImage = $annonce->getImage();
                if ($oldImage) {
                    $oldImagePath = $this->getParameter('images_directory') . '/' . $oldImage;
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
                $annonce->setImage($newFilename);
            }
            $entityManager->flush();

            $this->addFlash('success', 'Annonce modifiée avec succès !');
            return $this->redirectToRoute('guest-annonces');
        }

        return $this->render('guest/annonces/annonce-update.html.twig', [
            'form' => $form->createView(),
            'annonce' => $annonce,
        ]);
    }

    #[Route('/Guest/annonces/{id}/delete', name: 'guest-annonce-delete', methods: ['POST'])]
    public function deleteAnnonce(int $id, Request $request, AnnonceRepository $annonceRepository, EntityManagerInterface $entityManager): Response
    {
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
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres annonces.');
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
}
