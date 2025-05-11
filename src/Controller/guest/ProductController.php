<?php


namespace App\Controller\guest;

use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController {

    //je créais une route pour afficher la liste des produits
    //et une autre pour afficher les détails d'un produit
    #[Route('/list-products', name:'list-products')]
    public function displayListProducts(ProductsRepository $productsRepository) {
        // la méthode findBy() permet de récupérer les produits publiés
        $productsPublished = $productsRepository->findBy(['isPublished' => true]);
            return $this->render('guest/product/list-products.html.twig', [
                'products' => $productsPublished
        ]);
    }

    #[Route('/details-product/{id}', name:'details-product')]
    public function displayDetailsProduct(ProductRepository $productRepository, $id) {
        // la méthode find() permet de récupérer un produit par son id
        //je récupère le produit correspondant à l'id passé en paramètre
        $product = $productRepository->find($id);

        return $this->render('guest/product/details-product.html.twig', [
            'product' => $product
        ]);
    }
}

