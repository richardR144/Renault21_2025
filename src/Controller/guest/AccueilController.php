<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\ArticleRepository;
use App\Repository\AnnonceRepository;


class AccueilController extends AbstractController
{
    #[Route(path: '/', name: 'accueil', methods: ['GET'])] 
    public function accueil(ArticleRepository $articleRepository, AnnonceRepository $annonceRepository): Response
    {
        // Derniers articles 
        $latestArticles = $articleRepository->findBy([],  ['createdAt' => 'DESC'], 3);
        // Dernières annonces 
        $latestAnnonces = $annonceRepository->findBy([], ['createdAt' => 'DESC'], 5);
        // Rendu de la page d'accueil avec les derniers articles et annonces
        return $this->render('accueil.html.twig', [
            'articles' => $latestArticles,
            'annonces' => $latestAnnonces,
            'siteName' => 'Renault 21 Club',
            'welcomeMessage' => 'Bienvenue dans la communauté Renault 21 !'
        ]);
    }
}
