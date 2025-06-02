<?php
namespace App\Controller\Guest;
use App\Repository\CategoryRepository;
use App\Repository\PieceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController {
    #[Route('/list-categories', name:'list-categories', methods: ['GET'])]
    public function listCategories(CategoryRepository $categoryRepository): Response {

        $categories = $categoryRepository->findAll();  // la méthode findAll() permet de récupérer toutes les catégories

        return $this->render('guest/category/list-categories.html.twig', [
            'categories' => $categories
        ]);
    }



    #[Route('/details-category/{id}', name:'details-category', methods: ['GET'])]
    public function detailsCategory(int $id, PieceRepository $pieceRepository, CategoryRepository $categoryRepository): Response {

        $category = $categoryRepository->find($id);
        $piece = []; // Initialise un tableau vide pour les pièces
        
        if ($category) {
            // Si la catégorie existe, récupère les pièces associées
            $piece = $pieceRepository->findBy([
                'category' => $category,
            ]);
        } else {
            return $this->redirectToRoute("404");
        }

        return $this->render('guest/pieces/details-category.html.twig', [
            'category' => $category,
            'pieces' => $piece
        ]);
    }




}