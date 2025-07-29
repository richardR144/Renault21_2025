<?php

namespace App\Controller\Guest;

use App\Repository\PieceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search-results', methods: ['GET'])]
    public function search(Request $request, PieceRepository $pieceRepository): Response
    {
        $query = $request->query->get('q', '');
        $pieces = [];
        
        if (!empty($query)) {
            // Recherche dans les pièces
            $pieces = $pieceRepository->searchByKeyword($query);
        }
        
        return $this->render('search/results.html.twig', [
            'query' => $query,
            'pieces' => $pieces,
            'total_results' => count($pieces)
        ]);
    }
}