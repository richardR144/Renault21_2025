<?php
namespace App\Controller\Guest;
use App\Repository\CategoryRepository;
use App\Repository\PieceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Category;
use App\Form\CategoryType;

class CategoryController extends AbstractController {

    #[Route('/list-categories', name:'list-categories', methods: ['GET'])]
    public function listCategories(CategoryRepository $categoryRepository): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $categories = $categoryRepository->findAll();  // la méthode findAll() permet de récupérer toutes les catégories

        return $this->render('guest/category/list-categories.html.twig', [
            'categories' => $categories
        ]);
    }



    #[Route('/details-category/{id}', name:'details-category', methods: ['GET'])]
    public function detailsCategory(int $id, PieceRepository $pieceRepository, CategoryRepository $categoryRepository): Response
{
    $this->denyAccessUnlessGranted('ROLE_USER');
    $category = $categoryRepository->find($id);
    if (!$category) {
        throw $this->createNotFoundException('Catégorie non trouvée');
    }

    // Récupère les pièces associées à la catégorie
    $pieces = $pieceRepository->findBy([
        'category' => $category,
    ]);

    return $this->render('guest/category/details-category.html.twig', [
        'category' => $category,
        'pieces' => $pieces
    ]);
}

}
