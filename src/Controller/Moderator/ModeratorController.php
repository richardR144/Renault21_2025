<?php

namespace App\Controller\Moderator;

use App\Entity\Piece;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PieceRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;



#[IsGranted('ROLE_MODERATOR')]
class ModeratorController extends AbstractController
{
    #[Route('/moderator/articles', name: 'moderator-list-articles')]
    public function listArticles(ArticleRepository $repository): Response
    {
        
        $articles = $repository->findAll();
        return $this->render('moderator/list-articles.html.twig', [
            'articles' => $articles,
        ]);
    }
        
    #[Route('/moderator/article/{id}/update', name: 'moderator-update-article')]
    public function updateArticle(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $article->setTitle($request->request->get('title'));
            $article->setContent($request->request->get('content'));

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $imageFileName = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('article_images_directory'), $imageFileName);
                $article->setImage($imageFileName);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Article modéré avec succès !');
            return $this->redirectToRoute('moderator-list-articles');
        }

        return $this->render('moderator/update-article.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/moderator/pieces', name: 'moderator-list-pieces')]
    public function listPieces(PieceRepository $repository): Response
    {
        $pieces = $repository->findAll();
        return $this->render('moderator/list-pieces.html.twig', [
            'pieces' => $pieces,
        ]);
    }


    #[Route('/moderator/piece/{id}/update', name: 'moderator-update-piece')]
    public function updatePiece(Request $request, Piece $piece, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response 
    {
    if ($request->isMethod('POST')) {
        $name = $request->request->get('name');
        if ($name === null || $name === '') {
            $this->addFlash('error', 'Le nom de la pièce est obligatoire.');
            return $this->redirectToRoute('moderator-update-piece', ['id' => $piece->getId()]);
        }
        $piece->setName($name);
        $piece->setDescription($request->request->get('description'));
        $piece->setPrice($request->request->get('price'));

            $categoryId = $request->request->get('category-id');
            if ($categoryId) {
                $category = $categoryRepository->find($categoryId);
                if ($category) {
                    $piece->setCategory($category);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Pièce modérée avec succès !');
            return $this->redirectToRoute('moderator-list-pieces');
        }

        return $this->render('moderator/update-piece.html.twig', [
            'piece' => $piece,
        ]);
    }
}