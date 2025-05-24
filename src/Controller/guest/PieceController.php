<?php


namespace App\Controller\guest;


use App\Repository\PieceRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Piece;
use \Exception;

class PieceController extends AbstractController {
    
    #[Route('/user/create-piece', name: 'user-create-piece', methods: ['GET', 'POST'])]
    public function displayCreatePiece(CategoryRepository $categoryRepository, Request $request, EntityManagerInterface $entityManager, \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameterBag): Response
    {

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $price = $request->request->get('price');
            $userId = $request->request->get(key: 'userId');
            $categoryId = $request->request->get('category-id');

            $piece = new Piece();

            $piece = $categoryRepository->find($userId);
            $category = $categoryRepository->find($categoryId);


            try {
                // j'envoie le nom de l'image au constructeur de piece pour
                // stocker le nom de l'image dans le produit
                $piece = new Piece($title, $description, $price, $category); //envoyer une catégory complète

                $entityManager->persist($piece);
                $entityManager->flush();

                $this->addFlash('success', 'pièce créé');

                return $this->redirectToRoute('admin-list-pieces');

            } catch (Exception $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('guest/create-piece.html.twig', [
            'categories' => $category
        ]);
    }



    #[Route('/list-pieces', name:'guest-list-pieces', methods: ['GET'])]
    public function displayListPieces(PieceRepository $pieceRepository): Response {
        
        $pieces = $pieceRepository->findAll(['title' => true]);

        return $this->render('guest/piece/list-pieces.html.twig', [
            'pieces' => $pieces
        ]);
    }

    #[Route('/guest/delete-piece/{id}', name:'guest-delete-piece', methods: ['GET'])] //Exo 15
    public function deletePiece(int $id, PieceRepository $pieceRepository, EntityManagerInterface $entityManager): Response {

        $piece = $pieceRepository->find($id);
        // Si le produit n'existe pas, redirige vers la page 404 admin
        if(!$piece) {
            return $this->redirectToRoute('admin_404');
        }

        try {
            // Supprime le produit de la base de données
            $entityManager->remove($piece);
            $entityManager->flush();

            // Ajoute un message flash de succès
            $this->addFlash('success', 'Piece supprimé !');

        } catch(Exception $exception) {
            // En cas d'erreur, ajoute un message flash d'erreur
            $this->addFlash('error', 'Impossible de supprimer le piece');
        }

        return $this->redirectToRoute('admin-list-pieces');
    }

    #[Route('/admin/update-piece/{id}', name: 'admin-update-piece', methods: ['GET', 'POST'])]
    public function displayUpdatePiece(int $id, PieceRepository $pieceRepository, Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager): Response {

        $piece = $pieceRepository->find($id);

        if ($request->isMethod('POST')) {

            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $exchange = $request->request->get(key: 'exchange');
            $price = $request->request->get('price');
            $categoryId = $request->request->get('category-id');
            $userId = $request->request->get(key: 'userId');



            $category = $categoryRepository->find($categoryId);


            // méthode 2 : modifier les données d'une piece avec une fonction update dans l'entité

            try {
                $piece->update($title, $description, $exchange, $price, $category);

                $entityManager->persist($piece);
                $entityManager->flush();

                $this->addFlash('success', 'Piece supprimée !');

            } catch (\Exception $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }
        $categories = $categoryRepository->findAll();

        return $this->render('admin/pieces/update-piece.html.twig', [
            'categories' => $categories,
            'piece' => $piece
        ]);
    }

    #[Route('/details-piece/{id}', name:'details-piece', methods: ['GET'])]
    public function displayDetailsPiece(PieceRepository $pieceRepository, int $id): Response {

        $piece = $pieceRepository->find($id);

        if(!$piece) {
            return $this->redirectToRoute("404");
        }

        return $this->render('guest/pieces/details-piece.html.twig', [
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
