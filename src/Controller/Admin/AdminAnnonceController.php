<?php

namespace App\Controller\Admin;

use App\Entity\Annonce;
use App\Repository\AnnonceRepository;
use App\Repository\PieceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]

class AdminAnnonceController extends AbstractController
{
    #[Route('/admin/annonces', name: 'admin-list-annonces', methods: ['GET'])]  
    public function listAnnonce(AnnonceRepository $annonceRepository): Response
{
    $annonces = $annonceRepository->findBy([], ['createdAt' => 'DESC']);   

    return $this->render('admin/annonces/list-annonces.html.twig', [
        'annonces' => $annonces
    ]);
}

    #[Route('/admin/annonces/create', name: 'admin-create-annonce', methods: ['GET', 'POST'])]  // ✅ Cohérent
    public function createAnnonce(Request $request, EntityManagerInterface $entityManager, PieceRepository $pieceRepository): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create_annonce', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide');
                return $this->redirectToRoute('admin-create-annonce');
            }

            try {
                //Validation données
                $validatedData = $this->validateAnnonceData($request);

                //Récupération piece
                $pieceId = $request->request->get('piece_id');
                $piece = $pieceRepository->find($pieceId);

                if (!$piece) {
                    $this->addFlash('error', 'Pièce introuvable');
                    return $this->redirectToRoute('admin-create-annonce');
                }

                $annonce = new Annonce();
                $annonce->setTitle($validatedData['title']);           //Utiliser title
                $annonce->setDescription($validatedData['description']);
                $annonce->setEmail($validatedData['email']);           //Pas d'assignment
                $annonce->setCreatedAt(new \DateTimeImmutable());
                $annonce->setSender($this->getUser());
                $annonce->setPiece($piece);                            //setPiece() une seule fois avec objet

                //Upload sécurisé
                $imageFileName = $this->handleImageUpload($request);
                if ($imageFileName) {
                    $annonce->setImage($imageFileName);
                }

                $entityManager->persist($annonce);
                $entityManager->flush();

                $this->addFlash('success', 'Annonce créée avec succès !');
                return $this->redirectToRoute('admin-list-annonces');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de l\'annonce');
            }
        }

        $pieces = $pieceRepository->findAll();  // Récupérer toutes les pièces pour le formulaire

        return $this->render('admin/annonces/create-annonce.html.twig', [
            'pieces' => $pieces
        ]);
    }

    #[Route('/admin/annonces/delete/{id}', name: 'admin-delete-annonce', methods: ['POST'])]  
    public function deleteAnnonce(int $id, Request $request, AnnonceRepository $annonceRepository, EntityManagerInterface $entityManager): Response
{
    // CSRF Protection
    if (!$this->isCsrfTokenValid('delete_annonce_' . $id, $request->request->get('_token'))) {
        $this->addFlash('error', 'Token de sécurité invalide');
        return $this->redirectToRoute('admin-list-annonces');
    }

    $annonce = $annonceRepository->find($id);

    if (!$annonce) {
        $this->addFlash('error', 'Annonce introuvable');
        return $this->redirectToRoute('admin-list-annonces');
    }

    try {
        //Supprimer image physique  
        if ($annonce->getImage()) {
            $imagePath = $this->getParameter('annonces_images_directory') . '/' . $annonce->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $entityManager->remove($annonce);
        $entityManager->flush();

        $this->addFlash('success', 'Annonce supprimée avec succès !');
    } catch (\Exception $exception) {
        $this->addFlash('error', 'Impossible de supprimer l\'annonce');
    }

    return $this->redirectToRoute('admin-list-annonces');  // ✅ Cohérent
}

    #[Route('/admin/annonces/{id}', name: 'admin-show-annonce', methods: ['GET'])]  // ✅ Cohérent
    public function showAnnonce(int $id, AnnonceRepository $annonceRepository): Response
    {
        $annonce = $annonceRepository->find($id);

        if (!$annonce) {
            $this->addFlash('error', 'Annonce introuvable');
            return $this->redirectToRoute('admin-list-annonces');
        }

        return $this->render('admin/annonces/show-annonce.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/admin/annonces/{id}/update', name: 'admin-update-annonce', methods: ['GET', 'POST'])]
    public function updateAnnonce(int $id, Request $request, AnnonceRepository $annonceRepository, EntityManagerInterface $entityManager, PieceRepository $pieceRepository): Response
{
    $annonce = $annonceRepository->find($id);
    
    if (!$annonce) {
        $this->addFlash('error', 'Annonce introuvable');
        return $this->redirectToRoute('admin-list-annonces');
    }

    if ($request->isMethod('POST')) {
        if (!$this->isCsrfTokenValid('update_annonce_' . $annonce->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin-update-annonce', ['id' => $annonce->getId()]);
        }

        try {
            // Validation et mise à jour des données
            $title = trim($request->request->get('title'));
            $description = trim($request->request->get('description'));
            $email = trim($request->request->get('email'));
            $pieceId = $request->request->get('piece_id');
            $price = $request->request->get('price');
            $type = $request->request->get('type', 'sale');
            $exchangeDescription = trim($request->request->get('exchange_description'));

            // Validation
            if (empty($title) || strlen($title) < 5 || strlen($title) > 255) {
                throw new \Exception('Le titre doit contenir entre 5 et 255 caractères');
            }

            if (empty($description) || strlen($description) < 10 || strlen($description) > 10000) {
                throw new \Exception('La description doit contenir entre 10 et 10000 caractères');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Email invalide');
            }

            // Trouver la pièce
            $piece = $pieceRepository->find($pieceId);
            if (!$piece) {
                throw new \Exception('Pièce invalide');
            }

            // Mise à jour
            $annonce->setTitle(htmlspecialchars($title));
            $annonce->setDescription(htmlspecialchars($description));
            $annonce->setEmail(htmlspecialchars($email));
            $annonce->setPiece($piece);
            $annonce->setType($type);
            
            if ($type === 'sale' && !empty($price)) {
                $annonce->setPrice((float)$price);
                $annonce->setExchangeDescription(null);
            } elseif ($type === 'exchange') {
                $annonce->setPrice(null);
                $annonce->setExchangeDescription(htmlspecialchars($exchangeDescription));
            }

            // Gestion upload image
            $imageFileName = $this->handleImageUpload($request);
            if ($imageFileName) {
                // Supprimer ancienne image
                if ($annonce->getImage()) {
                    $oldImagePath = $this->getParameter('annonces_images_directory') . '/' . $annonce->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $annonce->setImage($imageFileName);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Annonce modifiée avec succès !');
            
            return $this->redirectToRoute('admin-show-annonce', ['id' => $annonce->getId()]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    $pieces = $pieceRepository->findAll();
    
    return $this->render('admin/annonces/update-annonce.html.twig', [
        'annonce' => $annonce,
        'pieces' => $pieces
    ]);
}

    //Methods privées 
    private function validateAnnonceData(Request $request): array
    {
        $title = trim($request->request->get('title', ''));
        $description = trim($request->request->get('description', ''));
        $email = trim($request->request->get('email', ''));

        if (strlen($title) < 5 || strlen($title) > 255) {
            throw new \InvalidArgumentException('Le titre doit contenir entre 5 et 255 caractères');
        }

        if (strlen($description) < 10 || strlen($description) > 10000) {
            throw new \InvalidArgumentException('La description doit contenir entre 10 et 10000 caractères');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        return [
            'title' => htmlspecialchars($title),
            'description' => htmlspecialchars($description),
            'email' => $email
        ];
    }

    private function handleImageUpload(Request $request): ?string
    {
        $imageFile = $request->files->get('image');

        if (!$imageFile) {
            return null;
        }

        //Validation MIME
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageFile->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Format d\'image non autorisé');
        }

        //Validation taille (5MB max)
        if ($imageFile->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Image trop volumineuse (max 5MB)');
        }

        //Extension sécurisée
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = $imageFile->guessExtension();
        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException('Extension non autorisée');
        }

        //Nom sécurisé
        $filename = uniqid() . '_' . date('Y-m-d') . '.' . $extension;

        try {
            $imageFile->move($this->getParameter('annonces_images_directory'), $filename);
            return $filename;
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de l\'upload');
        }
    }
}
