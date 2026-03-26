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
        $query = trim((string) $request->query->get('q', ''));
        $categoryId = $request->query->getInt('category', 0);
        $mode = (string) $request->query->get('mode', '');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 9;

        $minPriceFloat = $minPrice !== null && $minPrice !== '' ? (float) $minPrice : null;
        $maxPriceFloat = $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : null;

        $total = $pieceRepository->countSearchAdvanced(
            $query !== '' ? $query : null,
            $categoryId > 0 ? $categoryId : null,
            in_array($mode, ['vente', 'echange'], true) ? $mode : null,
            $minPriceFloat,
            $maxPriceFloat
        );

        $totalPages = max(1, (int) ceil($total / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $pieces = $pieceRepository->searchAdvanced(
            $query !== '' ? $query : null,
            $categoryId > 0 ? $categoryId : null,
            in_array($mode, ['vente', 'echange'], true) ? $mode : null,
            $minPriceFloat,
            $maxPriceFloat,
            $page,
            $limit
        );

        return $this->render('search/results.html.twig', [
            'query' => $query,
            'pieces' => $pieces,
            'categories' => $categoryRepository->findAll(),
            'selectedCategory' => $categoryId,
            'selectedMode' => $mode,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'page' => $page,
            'totalPages' => $totalPages,
            'total_results' => $total,
        ]);
    }
}