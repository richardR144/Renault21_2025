<?php

namespace App\Controller\Guest;

use App\Repository\PieceRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search-results', methods: ['GET'])]
    public function search(Request $request, PieceRepository $pieceRepository, CategoryRepository $categoryRepository): Response
{
    $query = $request->query->get('q', '');
    
    if (empty($query)) {
        return $this->redirectToRoute('list-pieces');
    }
    
    $pieces = $pieceRepository->findFuzzyByName($query);
    $categories = $categoryRepository->findBySearchTerm($query);
    
    return $this->render('search/results.html.twig', [
        'query' => $query,
        'pieces' => $pieces,
        'categories' => $categories,
        'total_results' => count($pieces)
    ]);
}
}