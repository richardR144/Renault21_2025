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

class PieceController extends AbstractController {  //AbstractController permet d'utiliser les méthodes  Symfony comme render, redirectToRoute, etc.
    
    #[Route('/guest/pieces/create-piece', name: 'create-piece', methods: ['GET', 'POST'])]
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
            $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFileName = $slugger->slug($originalFileName);
            $newFileName = $safeFileName.'-'.uniqid().'.'.$imageFile->guessExtension();
            
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
    


    #[Route('/Guest/pieces/list-pieces', name:'list-pieces', methods: ['GET'])]
    public function listPieces(PieceRepository $pieceRepository): Response {
        
        $pieces = $pieceRepository->findAll();

        return $this->render('Guest/pieces/list-pieces.html.twig', [
            'pieces' => $pieces
        ]);
    }


    #[Route('/Guest/pieces/update-piece/{id}', name: 'update-piece', methods: ['GET', 'POST'])]
    public function updatePiece(int $id, PieceRepository $pieceRepository, UserRepository $userRepository, Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response {
        
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $piece = $pieceRepository->find($id);
        $categories = $categoryRepository->findAll();

        $form = $this->createForm(InsertPieceForm::class, $piece);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $piece->setUser($user);

            $exchange = $form->get('exchange')->getData();
            $price = $form->get('price')->getData();

        // Vérification : si "vente" et pas de prix, on bloque
        if ($exchange === 'vente' && (is_null($price) || $price === '')) {
            $this->addFlash('error', 'Le prix est obligatoire pour une vente.');
                return $this->render('guest/pieces/insertPiece.html.twig', [
                    'insertPieceForm' => $form->createView(),
        ]);
    }
    // ... gestion image ...
        $imageFile = $form->get('image')->getData();
    if ($imageFile) {
        $originalFileName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $slugger->slug($originalFileName);
        $newFileName = $safeFileName.'-'.uniqid().'.'.$imageFile->guessExtension();
        $imageFile->move($this->getParameter('pieces_images_directory'), $newFileName);
        $piece->setImage($newFileName);
    }

    $entityManager->persist($piece);
    $entityManager->flush();

    $this->addFlash('success', 'pièce créée avec succès !');
    return $this->redirectToRoute('list-pieces');
}

            return $this->render('guest/pieces/update-piece.html.twig', [
               'insertPieceForm' => $form->createView(),
            ]);
        }
    


    #[Route('/Guest/pieces/delete-piece/{id}', name:'delete-piece', methods: ['GET', 'POST'])]
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

    #[Route('/Guest/pieces/details-piece/{id}', name:'details-piece', methods: ['GET'])]
    public function detailsPiece(PieceRepository $pieceRepository, int $id): Response {

        $piece = $pieceRepository->find($id);

        if(!$piece) {
            throw $this->createNotFoundException('Pièce non trouvée');
        }

        return $this->render('guest/pieces/details-piece.html.twig', [
            'piece' => $piece,
        ]);
    }

    /* Serach-result pour rechercher une pièce précise et récupérer le champs tapé dans la recherche
    #[Route('/Guest/pieces/search-results', name:'search-results', methods: ['GET'])]
    public function resultsSearchPieces(Request $request, PieceRepository $pieceRepository): Response{
    $query = $request->query->get('q', ''); // je récupère le texte tapé dans le champ de recherche q dans l'url
    // Si le champ de recherche est vide, on initialise $pieces à un tableau vide
    $pieces = [];

    if ($query) {  // Si le champ de recherche n'est pas vide, j'effectue la recherche
        $pieces = $pieceRepository->createQueryBuilder('p') //j'utilise le QueryBuilder pour construire ma requête
            ->where('p.name LIKE :q OR p.description LIKE :q') // je remplace le texte tapé par un LIKE pour chercher dans la base de données
            ->setParameter('q', '%' . $query . '%') // je cherche dans le nom et la description de la pièce
            ->orderBy('p.createdAt', 'DESC') // je trie les résultats par date de création décroissante
            ->getQuery()                     // je récupère la requête
            ->getResult();                   // je récupère les résultats de la requête
    }

    if (empty($pieces)) {
        $this->addFlash('info', 'Aucune pièce trouvée pour votre recherche.');
    }
    return $this->render('guest/pieces/search-results.html.twig', [
        'pieces' => $pieces  //j'affiche la page des résultats, en passant la liste des trouvées au twig    
    ]);
    }*/
   
}
