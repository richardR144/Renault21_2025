<?php


namespace App\Controller\guest;


use App\Repository\PieceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class PieceController extends AbstractController {

    #[Route('/list-pieces', name:'list-pieces', methods: ['GET'])]
    public function displayListPieces(PieceRepository $pieceRepository, EntityManagerInterface $entityManager): Response {

        $pieces = $entityManager->$pieceRepository->getRepository(Piece::class)->findAll(['title' => true]);

        return $this->render('guest/piece/list-pieces.html.twig', [
            'pieces' => $pieces
        ]);
    }

    #[Route('/details-piece/{id}', name:'details-piece', methods: ['GET'])]
    public function displayDetailsPiece(PieceRepository $pieceRepository, int $id): Response {

        $piece = $pieceRepository->find($id);

        if(!$piece) {
            return $this->redirectToRoute("404");
        }

        return $this->render('guest/pieces/details-product.html.twig', [
            'piece' => $piece
        ]);
    }

    #[Route(path: '/results-recherche', name:'piece-search-results', methods: ['GET'])]
    public function displayResultsSearchPieces(Request $request, PieceRepository $pieceRepository): Response
    {
        $search = $request->query->get('search');

        $piecesFound = $pieceRepository->findByTitleContain($search);


        return $this->render('guest/pieces/search-results.html.twig', [
            'piecesFound' => $piecesFound,
            'search' => $search
        ]);
    }
}
