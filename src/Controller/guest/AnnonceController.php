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
    #[Route('/Guest/annonces', name: 'annonces')]
    public function annonceController(AnnonceRepository $annonceRepository): Response
    {
        $annonces = $annonceRepository->findAll();
        return $this->render('Guest/annonces/index.html.twig', [
            'annonces' => $annonces,
        ]);
    }

    #[Route('/Guest/annonces/create', name: 'annonce-create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $annonce = new Annonce();
        $form = $this->createForm(Annonce::class, $annonce);

        $form->handleRequest($request); 
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($annonce);
            $entityManager->flush();

            return $this->redirectToRoute('Guest/annonces');
        }

        return $this->render('Guest/annonces/annonce-create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/Guest/annonces/{piece}', name: 'annonce-show', methods: ['GET'])]
    public function show(string $piece, AnnonceRepository $annonceRepository): Response
    {
        $annonce = $annonceRepository->find($piece);
        if (!$annonce) {
            throw $this->createNotFoundException('Annonce non trouvée');
        }
        return $this->render('Guest/annonces/annonce-show.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/Guest/annonces/{piece}/update', name: 'annonce-update', methods: ['GET', 'POST'])]
    public function update(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Annonce::class, $annonce);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('Guest/annonces');
        }

        return $this->render('Guest/annonces/annonce-update.html.twig', [
            'form' => $form->createView(),
            'annonce' => $annonce,
        ]);
    }


    #[Route('/Guest/annonces/{piece}/delete-confirm', name: 'annonce-delete-confirm', methods: ['GET'])]
    public function deleteConfirm(Annonce $annonce): Response
{
        return $this->render('Guest/annonces/annonce-delete-confirm.html.twig', [
            'annonce' => $annonce,
    ]);
}

    
    #[Route('/Guest/annonces/list', name: 'annonce-list', methods: ['GET'])]
    public function listAnnonces(AnnonceRepository $annonceRepository): Response
    {
        $annonces = $annonceRepository->findAll();
        return $this->render('Guest/annonces/annonce-list.html.twig', [
            'annonces' => $annonces,
        ]);
    }
}