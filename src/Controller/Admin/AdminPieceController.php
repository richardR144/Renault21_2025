<?php

namespace App\Controller\Admin;



use App\Entity\Category;
use App\Entity\Piece;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminPieceController extends AbstractController
{
    #[Route('/admin/create-piece', name: 'admin-create-piece', methods: ['GET', 'POST'])]
    public function createPiece(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();

if ($request->isMethod('POST')) {
    if (!$this->isCsrfTokenValid('create_piece', $request->request->get('_token'))) {
        $this->addFlash('error', 'Token de sécurité invalide');
        return $this->redirectToRoute('admin-create-piece');
    }

    try {
        $name = trim((string) $request->request->get('name'));
        $description = trim((string) $request->request->get('description'));
        $exchange = $request->request->get('exchange');
        $exchange = $exchange === '1' || $exchange === 1 || $exchange === true; // Vérification de l'échange
        $price = $this->normalizePrice($request->request->get('price'));
        $categoryId = $request->request->get('categoryId');
        $image = $request->files->get('image'); 

        if ($name === '' || $description === '') {
            $this->addFlash('error', 'Le nom et la description sont obligatoires.');
            return $this->redirectToRoute('admin-create-piece');
        }

        if ($exchange && $price === null) {
            $this->addFlash('error', 'Le prix est obligatoire pour une vente.');
            return $this->redirectToRoute('admin-create-piece');
        }

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
        
        if ($image instanceof UploadedFile) {
            $imageFileName = $this->uploadPieceImage($image);
            $piece->setImage($imageFileName);
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
            'categories' => $categories
        ]);
    }


#[Route('/admin/list-pieces', name: 'admin-list-pieces', methods: ['GET'])]
public function listPieces(EntityManagerInterface $entityManager): Response {
    $pieceRepository = $entityManager->getRepository(Piece::class);
    $pieces = $pieceRepository->findAll();

    return $this->render('admin/piece/list-pieces.html.twig', [
        'pieces' => $pieces
    ]);
}

    #[Route('/admin/delete-piece/{id}', name:'admin-delete-piece', methods: ['POST'])] 
    public function deletePiece(int $id, EntityManagerInterface $entityManager, Request $request): Response
{
    $pieceRepository = $entityManager->getRepository(Piece::class);
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
    public function updatePiece(int $id, Request $request, EntityManagerInterface $entityManager): Response {

        $pieceRepository = $entityManager->getRepository(Piece::class);
        $categoryRepository = $entityManager->getRepository(Category::class);

        $piece = $pieceRepository->find($id);
        if (!$piece) {
            $this->addFlash('error', 'Pièce introuvable');
            return $this->redirectToRoute('admin-list-pieces');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('update_piece' . $piece->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide');
                return $this->redirectToRoute('admin-update-piece', ['id' => $piece->getId()]);
            }

            $name = trim((string) $request->request->get('name'));
            $description = trim((string) $request->request->get('description'));
            $exchange = $request->request->get('exchange');
            $exchange = $exchange === '1' || $exchange === 1 || $exchange === true; // Vérification de l'échange
            $price = $this->normalizePrice($request->request->get('price'));
            $categoryId = $request->request->get('categoryId');
            $image = $request->files->get('image'); // Récupération de l'image

            if ($name === '' || $description === '') {
                $this->addFlash('error', 'Le nom et la description sont obligatoires.');
                return $this->redirectToRoute('admin-update-piece', ['id' => $piece->getId()]);
            }

            if ($exchange && $price === null) {
                $this->addFlash('error', 'Le prix est obligatoire pour une vente.');
                return $this->redirectToRoute('admin-update-piece', ['id' => $piece->getId()]);
            }

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
                $piece->setName($name);
                $piece->setDescription($description);
                $piece->setExchange($exchange);
                $piece->setPrice($price);
                $piece->setCategory($category);
                if ($image instanceof UploadedFile) {
                    if ($piece->getImage()) {
                        $oldImagePath = $this->getParameter('pieces_images_directory') . '/' . $piece->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $imageFileName = $this->uploadPieceImage($image);
                    $piece->setImage($imageFileName);
                }

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

    private function uploadPieceImage(UploadedFile $imageFile): string
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageFile->getMimeType(), $allowedMimes, true)) {
            throw new \InvalidArgumentException('Format d\'image non autorisé');
        }

        if ($imageFile->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Image trop volumineuse (max 5MB)');
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = $imageFile->guessExtension();
        if (!$extension || !in_array($extension, $allowedExtensions, true)) {
            throw new \InvalidArgumentException('Extension non autorisée');
        }

        $newFileName = uniqid('piece_', true) . '.' . $extension;
        $imageFile->move($this->getParameter('pieces_images_directory'), $newFileName);

        return $newFileName;
    }

    private function normalizePrice(mixed $priceInput): ?float
    {
        if ($priceInput === null) {
            return null;
        }

        $priceString = trim((string) $priceInput);
        if ($priceString === '') {
            return null;
        }

        $normalized = str_replace([',', ' '], ['.', ''], $priceString);
        if (!is_numeric($normalized)) {
            throw new \InvalidArgumentException('Le prix doit être un nombre valide (ex: 1500 ou 1500,50).');
        }

        return (float) $normalized;
    }
}