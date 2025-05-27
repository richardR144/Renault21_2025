<?php

namespace App\Controller\Guest;
use App\Entity\Annonce;
use App\Repository\AnnonceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class AnnonceController extends AbstractController
{
    #[Route('/guest/annonces', name: 'guest-annonces')]
    public function annonceController(AnnonceRepository $annonceRepository): Response
    {
        $annonces = $annonceRepository->findAll();
        return $this->render('guest/annonces/index.html.twig', [
            'annonces' => $annonces,
        ]);
    }

    #[Route('/guest/annonces/create', name: 'guest-annonces-create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $annonce = new Annonce();
        $form = $this->createForm(Annonce::class, $annonce);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($annonce);
            $entityManager->flush();

            return $this->redirectToRoute('guest/annonces');
        }

        return $this->render('guest/annonces/annonce-create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/guest/annonces/{piece}', name: 'guest-annonces-show', methods: ['GET'])]
    public function show(string $piece, AnnonceRepository $annonceRepository): Response
    {
        $annonce = $annonceRepository->find($piece);
        if (!$annonce) {
            throw $this->createNotFoundException('Annonce non trouvée');
        }
        return $this->render('guest/annonces/annonce-show.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/guest/annonces/{annonce}/update', name: 'guest-annonces-update', methods: ['GET', 'POST'])]
    public function update(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Annonce::class, $annonce);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('guest/annonces');
        }

        return $this->render('guest/annonces/annonce-update.html.twig', [
            'form' => $form->createView(),
            'annonce' => $annonce,
        ]);
    }

    #[Route('/guest/annonces/{annonce}/deleteAnnonce', name: 'guest-annonces-delete', methods: ['POST'])]
    public function deleteAnnonce(Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($annonce);
        $entityManager->flush();

        return $this->redirectToRoute('guest/annonces');
    }

    #[Route('/guest/annonces/{annonce}/delete-confirm', name: 'guest/annonces/delete-confirm', methods: ['GET'])]
    public function deleteConfirm(Annonce $annonce): Response
{
        return $this->render('guest/annonces/annonce-delete-confirm.html.twig', [
            'annonce' => $annonce,
    ]);
}

    #[Route('/guest/annonces/update/{id}', name: 'guest-annonces-update', methods: ['GET', 'POST'])]
    public function updateAnnonce(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Annonce::class, $annonce);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('guest/annonces');
        }

        return $this->render('guest/annonces/annonce-update.html.twig', [
            'form' => $form->createView(),
            'annonce' => $annonce,
        ]);
    }

    public function listAnnonces(AnnonceRepository $annonceRepository): Response
    {
        $annonces = $annonceRepository->findAll();
        return $this->render('guest/annonces/annonce-list.html.twig', [
            'annonces' => $annonces,
        ]);
    }
}