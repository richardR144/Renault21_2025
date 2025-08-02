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
        $category = $categoryRepository->findAll();

if ($request->isMethod('POST')) {
    try {
        $name= $request->request->get('name');
        $description = $request->request->get('description');
        $exchange = $request->request->get('exchange');
        $exchange = $exchange === '1' || $exchange === 1 || $exchange === true ? true : false; // Vérification de l'échange
        $price = $request->request->get('price');
        $userId = $request->request->get(key: 'userId');
        $categoryId = $request->request->get('categoryId');
        $image = $request->files->get('image'); 

        if (!$categoryId) {
            $this->addFlash('error', 'Veuillez sélectionner une catégorie.');
            return $this->redirectToRoute('admin-create-piece');
        }
        
        $category = $categoryRepository->find($categoryId);

        if (!$category) {
            $this->addFlash('error', 'Catégorie introuvable.');
            return $this->redirectToRoute('admin-create-piece');
        }

        $piece = new Piece();

        $piece->setName($name);
        $piece->setDescription($description);
        $piece->setExchange($exchange);
        $piece->setPrice($price);
        $piece->setCategory($category);
        
        if ($image) {
            $piece->setImage($image);
        }

        $entityManager->persist($piece);
        $entityManager->flush();

        $this->addFlash('success', 'Pièce créée avec succès !');
        return $this->redirectToRoute('admin-list-pieces');

    } catch (Exception $exception) {
        $this->addFlash('error', $exception->getMessage());
    }
}
        return $this->render('admin/piece/create-piece.html.twig', [
            'categories' => $category
        ]);
    }


#[Route('/admin/list-pieces', name: 'admin-list-pieces', methods: ['GET'])]
public function listPieces(PieceRepository $pieceRepository): Response {
    $pieces = $pieceRepository->findAll();

    return $this->render('admin/piece/list-pieces.html.twig', [
        'pieces' => $pieces
    ]);
}

    #[Route('/admin/delete-piece/{id}', name:'admin-delete-piece', methods: ['POST'])] 
    public function deletePiece(int $id, PieceRepository $pieceRepository, EntityManagerInterface $entityManager, Request $request): Response
{
    //CSRF Protection
    if (!$this->isCsrfTokenValid('delete_piece_' . $id, $request->request->get('_token'))) {
        $this->addFlash('error', 'Token de sécurité invalide');
        return $this->redirectToRoute('admin-list-pieces');
    }

    $piece = $pieceRepository->find($id);
    
    if (!$piece) {
        $this->addFlash('error', 'Pièce introuvable');
        return $this->redirectToRoute('admin-list-pieces');
    }

    try {
        $entityManager->remove($piece);
        $entityManager->flush();
        $this->addFlash('success', 'Pièce supprimée avec succès !');
    } catch (Exception $exception) {
        $this->addFlash('error', 'Impossible de supprimer la pièce');
    }

    return $this->redirectToRoute('admin-list-pieces');
}
    

    #[Route('/admin/update-piece/{id}', name: 'admin-update-piece', methods: ['GET', 'POST'])]
    public function updatePiece(int $id, PieceRepository $pieceRepository, Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager): Response {

        $piece = $pieceRepository->find($id);

        if ($request->isMethod('POST')) {

            $Name = $request->request->get('name');
            $description = $request->request->get('description');
            $exchange = $request->request->get('exchange');
            $exchange = $exchange === '1' || $exchange === 1 || $exchange === true ? true : false; // Vérification de l'échange
            $price = $request->request->get('price');
            $categoryId = $request->request->get('categoryId');
            $image = $request->files->get('image'); // Récupération de l'image
            if (!$categoryId) {
                $this->addFlash('error', 'Veuillez sélectionner une catégorie.');
                return $this->redirectToRoute('admin-update-piece', ['id' => $piece->getId()]);
}
            $category = $categoryRepository->find($categoryId);
            if (!$category) {
                $this->addFlash('error', 'Catégorie introuvable.');
                return $this->redirectToRoute('admin-update-piece', ['id' => $piece->getId()]);
}
            
            try {
                $piece->setName($Name);
                $piece->setDescription($description);
                $piece->setExchange($exchange);
                $piece->setPrice($price);
                $piece->setCategory($category);
                $piece->setImage($image);

                $entityManager->persist($piece);
                $entityManager->flush();

                $this->addFlash('success', 'Piece modifiée !');

            } catch (\Exception $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }
        $categories = $categoryRepository->findAll();

        return $this->render('admin/piece/update-piece.html.twig', [
            'categories' => $categories,
            'piece' => $piece
        ]);
    }
}