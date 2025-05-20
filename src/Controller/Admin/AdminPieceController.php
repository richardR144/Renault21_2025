<?php

namespace App\Controller\Admin;



use App\Entity\Piece;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminPieceController extends AbstractController
{
    #[Route('/admin/create-piece', name: 'admin-create-piece', methods: ['GET', 'POST'])]
    public function displayCreatePiece(CategoryRepository $categoryRepository, Request $request, EntityManagerInterface $entityManager, \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameterBag): Response
    {

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $price = $request->request->get('price');
            $userId = $request->request->get(key: 'userId');
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
    #[Route('/admin/list-piece', name: 'admin-list-pieces', methods: ['GET'])]
    public function displayListProduct(PieceRepository $pieceRepository): Response {
        $products = $pieceRepository->findAll();

        return $this->render('admin/pieces/list-pieces.html.twig', [
            'products' => $products
        ]);
    }

}