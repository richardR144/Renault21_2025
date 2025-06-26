<?php

namespace App\Controller\Moderator;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ArticleRepository;
use App\Repository\PieceRepository;


#[IsGranted('ROLE_MODERATOR')]
class ModeratorDashboardController extends AbstractController
{
    #[Route('/moderator', name: 'moderator-dashboard')]
    public function index(): Response
    {
        return $this->render('moderator/dashboard.html.twig');
    }

    #[Route('/moderator/articles', name: 'moderator-list-articles')]
    public function listArticles(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();
        return $this->render('moderator/list-articles.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/moderator/pieces', name: 'moderator-list-pieces')]
    public function listPieces(PieceRepository $pieceRepository): Response
    {
        $pieces = $pieceRepository->findAll();
        return $this->render('moderator/list-pieces.html.twig', [
            'pieces' => $pieces,
        ]);
    }
}