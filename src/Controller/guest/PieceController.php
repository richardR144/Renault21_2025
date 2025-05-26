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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\UserRepository;

class PieceController extends AbstractController {
    
    #[Route('/guest/pieces/create-piece', name: 'create-piece', methods: ['GET', 'POST'])]
    public function createPiece(CategoryRepository $categoryRepository, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, ParameterBagInterface $parameterBag): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
         $categories = $categoryRepository->findAll();

    if ($request->isMethod('POST')) {
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $price = $request->request->get('price');
        $categoryId = $request->request->get('category-id');
        $category = $categoryRepository->find($categoryId);
        $userId = $request->request->get('userId');
        $user = $userRepository->find($userId);
        $imageFile = $request->files->get('image');



    if (!$user) {
        $this->addFlash('error', 'Utilisateur introuvable.');
        return $this->redirectToRoute('guest-pieces-create-piece');
     }

    try {
        $piece = new Piece($title, $description, $price, $category);
        $piece->setUser($user);

        if ($imageFile) {
            $newFilename = uniqid().'.'.$imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('pieces_images_directory'), // à définir dans services.yaml
                $newFilename
            );
            $piece->setImage($newFilename); // Stocke le nom du fichier dans l'entité
        }

            $entityManager->persist($piece);
            $entityManager->flush();

            $this->addFlash('success', 'pièce créée avec succès !');
            return $this->redirectToRoute('guest-pieces-list-pieces');

        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());
        }
    }
            return $this->render('guest/pieces/create-piece.html.twig', [
                'categories' => $categories
    ]);
    }


    #[Route('/guest/pieces/list-pieces', name:'list-pieces', methods: ['GET'])]
    public function listPieces(PieceRepository $pieceRepository): Response {
        
        $pieces = $pieceRepository->findAll();

        return $this->render('guest/pieces/list-pieces.html.twig', [
            'pieces' => $pieces
        ]);
    }

    #[Route('/guest/pieces/update-piece/{id}', name: 'update-piece', methods: ['GET', 'POST'])]
    public function updatePiece(int $id, PieceRepository $pieceRepository, UserRepository $userRepository, Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager): Response {
        
        $this->denyAccessUnlessGranted('ROLE_USER');
        $piece = $pieceRepository->find($id);

        if (!$piece) {
                throw $this->createNotFoundException('Pièce non trouvée');
            }

        if ($request->isMethod('POST')) {

            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $exchange = $request->request->get('exchange');
            $price = $request->request->get('price');
            $categoryId = $request->request->get('category-id');
            $userId = $request->request->get('userId');
            $imageFile = $request->files->get('image');


            $imageFile = $request->files->get('image');
            $category = $categoryRepository->find($categoryId);
            $user = $userRepository->find($userId); //association de l'utilisateur à la pièce


            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move(
                $this->getParameter('pieces_images_directory'), // à définir dans services.yaml
                $newFilename);
                // Vérifie si une image précédente existe et la supprime si nécessaire
                $piece->setImage($newFilename); // Stocke le nom du fichier dans l'entité
    }

            if ($user) {
                $piece->setUser($user);
            } 


            // méthode 2 : modifier les données d'une piece avec une fonction update dans l'entité

            try {
                $piece->update($title, $description, $exchange, $price, $category);
                $piece->setImage($request->files->get('image')); // Mettre à jour l'image si elle est fournie
                
                /*$entityManager->persist($piece);*/ // Pas besoin de persist car l'entité est déjà gérée par Doctrine
                $entityManager->flush();

                $this->addFlash('success', 'Piece modifiée !');

            } catch (\Exception $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }
                $categories = $categoryRepository->findAll();

        return $this->render('guest/pieces/update-piece.html.twig', [
            'categories' => $categories,
            'piece' => $piece
        ]);
    }


    #[Route('/guest/pieces/delete-piece/{id}', name:'delete-piece', methods: ['GET'])] //Exo 15
    public function deletePiece(int $id, PieceRepository $pieceRepository, EntityManagerInterface $entityManager, Request $request): Response {

        $this->denyAccessUnlessGranted('ROLE_USER');
        $piece = $pieceRepository->find($id);
        // Si le produit n'existe pas, redirige vers la page 404 ou affiche un message d'erreur de Symfony
        if(!$piece) {
            throw $this->createNotFoundException('Pièce non supprimée, elle n\'existe pas');
        }

        if ($request->isMethod('POST')) {
        try {
            // Supprime le produit de la base de données
            $entityManager->remove($piece);
            $entityManager->flush();

            // Ajoute un message flash de succès
            $this->addFlash('success', 'Piece supprimée !');

        } catch(Exception $exception) {
            // En cas d'erreur, ajoute un message flash d'erreur
            $this->addFlash('error', 'Impossible de supprimer le piece');
        }

        return $this->redirectToRoute('guest-pieces-list-pieces');
    }
        // Sinon, on affiche la page de confirmation
        return $this->render('guest/pieces/delete-piece.html.twig', [
        'piece' => $piece
        ]);
    }

    #[Route('/guest/pieces/details-piece/{id}', name:'details-piece', methods: ['GET'])]
    public function detailsPiece(PieceRepository $pieceRepository, int $id): Response {

        $piece = $pieceRepository->find($id);

        if(!$piece) {
            throw $this->createNotFoundException('Pièce non trouvée');
        }

        return $this->render('guest/pieces/details-piece.html.twig', [
            'piece' => $piece
        ]);
    }


    #[Route(path: '/guest/pieces/results-recherche', name:'search-results', methods: ['GET'])]
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
