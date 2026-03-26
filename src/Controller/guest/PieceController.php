<?php


namespace App\Controller\Guest;


use App\Repository\PieceRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Piece;
use App\Form\InsertPieceForm;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\UserRepository;
use App\Form\InsertPieceType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class PieceController extends AbstractController
{  //AbstractController permet d'utiliser les méthodes  Symfony comme render, redirectToRoute, etc.

    #[Route('/Guest/pieces/create-piece', name: 'create-piece', methods: ['GET', 'POST'])]
    public function createPiece(CategoryRepository $categoryRepository, PieceRepository $pieceRepository, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, ParameterBagInterface $params, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Vérifie si l'utilisateur est connecté
        $user = $this->getUser();

        $categories = $categoryRepository->findAll();
        $params = $this->container->get('parameter_bag');
        $piece = new Piece();
        $form = $this->createForm(InsertPieceForm::class, $piece);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $piece->setUser($user);

            $exchange = $form->get('exchange')->getData();
            $price = $form->get('price')->getData();

            if ($exchange === 'vente' && (is_null($price) || $price === '')) { //Récupère la valeur sélectionnée pour le type d'annonce ("vente" ou "échange") depuis le formulaire
                $this->addFlash('error', 'Le prix est obligatoire pour une vente.');
                return $this->render('guest/pieces/insertPiece.html.twig', [
                    'insertPieceForm' => $form->createView(),
                ]);
            }
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $extension = $this->validateImageUpload($imageFile);
                $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $extension;

                $imageFile->move($this->getParameter('pieces_images_directory'), $newFileName);
                $piece->setImage($newFileName);
            }

            $entityManager->persist($piece);
            $entityManager->flush();

            $this->addFlash('success', 'pièce créée avec succès !');
            return $this->redirectToRoute('list-pieces');
        }
        return $this->render('guest/pieces/insertPiece.html.twig', [
            'insertPieceForm' => $form->createView(),
        ]);
    }



    #[Route('/Guest/pieces/list-pieces', name: 'list-pieces', methods: ['GET'])]
    public function listPieces(PieceRepository $pieceRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 6;

        $total = $pieceRepository->countTotal();
        $totalPages = max(1, (int) ceil($total / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;
        $pieces = $pieceRepository->findPaginated($offset, $limit);

        return $this->render('guest/pieces/list-pieces.html.twig', [
            'pieces' => $pieces,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }


    #[Route('/Guest/pieces/update-piece/{id}', name: 'update-piece', methods: ['GET', 'POST'])]
    public function updatePiece(int $id, PieceRepository $pieceRepository, UserRepository $userRepository, Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $piece = $pieceRepository->find($id);
        if (!$piece) {
            throw $this->createNotFoundException('Pièce non trouvée');
        }

        if ($piece->getUser() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres pièces.');
            return $this->redirectToRoute('show-user-piece');
        }

        $categories = $categoryRepository->findAll();

        $form = $this->createForm(InsertPieceForm::class, $piece);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $exchange = $form->get('exchange')->getData();
            $price = $form->get('price')->getData();

            // Vérification : si "vente" et pas de prix, on bloque
            if ($exchange === 'vente' && (is_null($price) || $price === '')) {
                $this->addFlash('error', 'Le prix est obligatoire pour une vente.');
                return $this->render('guest/pieces/update-piece.html.twig', [
                    'insertPieceForm' => $form->createView(),
                ]);
            }
            // ... gestion image ...
                $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $extension = $this->validateImageUpload($imageFile);
                $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($originalFileName);
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $extension;
                $imageFile->move($this->getParameter('pieces_images_directory'), $newFileName);
                $piece->setImage($newFileName);
            }

            $entityManager->flush();

            $this->addFlash('success', 'pièce modifiée avec succès !');
            return $this->redirectToRoute('list-pieces');
        }

        return $this->render('guest/pieces/update-piece.html.twig', [
            'insertPieceForm' => $form->createView(),
        ]);
    }



    #[Route('/Guest/pieces/delete-piece/{id}', name: 'delete-piece', methods: ['GET', 'POST'])]
    public function deletePiece(int $id, PieceRepository $pieceRepository, EntityManagerInterface $entityManager, Request $request): Response
    {

        $this->denyAccessUnlessGranted('ROLE_USER');
        $piece = $pieceRepository->find($id);
        // Si le produit n'existe pas, redirige vers la page 404 ou affiche un message d'erreur de Symfony
        if (!$piece) {
            throw $this->createNotFoundException('Pièce non supprimée, elle n\'existe pas');
        }

        if ($piece->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres pièces.');
            return $this->redirectToRoute('show-user-piece');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('delete_piece_' . $piece->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide');
                return $this->redirectToRoute('list-pieces');
            }

            try {
                // Supprime le produit de la base de données
                $entityManager->remove($piece);
                $entityManager->flush();

                // Ajoute un message flash de succès
                $this->addFlash('success', 'Piece supprimée !');
            } catch (\Exception $exception) {
                // En cas d'erreur, ajoute un message flash d'erreur
                $this->addFlash('error', 'Impossible de supprimer le piece');
            }

            return $this->redirectToRoute('list-pieces');
        }
        // Sinon, on affiche la page de confirmation
        return $this->render('guest/pieces/delete-piece.html.twig', [
            'piece' => $piece
        ]);
    }

    #[Route('/Guest/pieces/details-piece/{id}', name: 'details-piece', methods: ['GET'])]
    public function detailsPiece(PieceRepository $pieceRepository, int $id): Response
    {

        $piece = $pieceRepository->find($id);

        if (!$piece) {
            throw $this->createNotFoundException('Pièce non trouvée');
        }

        return $this->render('guest/pieces/details-piece.html.twig', [
            'piece' => $piece,
        ]);
    }

    #[Route('/Guest/pieces/show-user-piece', name: 'show-user-piece', methods: ['GET'])]
    public function showUserPieces(PieceRepository $pieceRepository): Response
    {
        // Sécurisation obligatoire
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Récupérer SEULEMENT les pièces de cet utilisateur
        $pieces = $pieceRepository->findBy(['user' => $user]);

        return $this->render('guest/show-user-piece.html.twig', [
            'pieces' => $pieces
        ]);
    }

    #[Route('/Guest/pieces/search-piece', name: 'search-piece', methods: ['GET'])]
    public function searchPiece(Request $request, PieceRepository $pieceRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $searchTerm = $request->query->get('q');
        $pieces = $pieceRepository->findBySearchTerm($searchTerm);

        return $this->render('guest/pieces/search-piece.html.twig', [
            'pieces' => $pieces,
            'searchTerm' => $searchTerm
        ]);
    }

    private function validateImageUpload(UploadedFile $imageFile): string
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

        return $extension;
    }
}
