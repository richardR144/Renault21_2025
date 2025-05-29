<?php

namespace App\Controller\Admin;



use App\Entity\Piece;
use App\Repository\CategoryRepository;
use App\Repository\PieceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminPieceController extends AbstractController
{
    #[Route('/admin/create-piece', name: 'admin-create-piece', methods: ['GET', 'POST'])]
    public function createPiece(CategoryRepository $categoryRepository, Request $request, EntityManagerInterface $entityManager, \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameterBag): Response
    {

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $price = $request->request->get('price');
            $userId = $request->request->get(key: 'userId');
            $image = $request->files->get('image'); 
            $categoryId = $request->request->get('category-id');

            $piece = new Piece();

            $user = $categoryRepository->find($userId);
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

        return $this->render('admin/create-piece.html.twig', [
            'category' => $category,
        ]);
    }

    
    #[Route('/admin/list-piece', name: 'admin-list-pieces', methods: ['GET', 'POST'])]
    public function listPieces(PieceRepository $pieceRepository): Response {
        $piece = $pieceRepository->findAll();

        return $this->render('admin/pieces/list-pieces.html.twig', [
            'pieces' => $piece
        ]);
    }

    #[Route('/admin/delete-piece/{id}', name:'admin-delete-piece', methods: ['GET'])] //Exo 15
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
    public function updatePiece(int $id, PieceRepository $pieceRepository, Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager): Response {

        $piece = $pieceRepository->find($id);

        if ($request->isMethod('POST')) {

            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $exchange = $request->request->get(key: 'exchange');
            $price = $request->request->get('price');
            $categoryId = $request->request->get('category-id');
            $userId = $request->request->get(key: 'userId');



            $category = $categoryRepository->find($categoryId);

            // méthode 1 : modifier les données d'une piece avec les fonctions setters
            //$piece->setTitle($title);
            //$piece->setDescription($description);
            //$piece->setPrice($price);
            //$piece->setcategory($category);


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
}