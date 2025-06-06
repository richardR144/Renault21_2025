<?php

namespace App\Controller\Guest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ArticleRepository as Repository;


class ShowArticleController extends AbstractController
{
    #[Route('/pantheon', name: 'list-articles')]
    public function listArticles(Request $request, Repository $repository, EntityManagerInterface $entityManager): Response
    {
        $articles = $repository->findAll();
        return $this->render('guest/pantheon/list-articles.html.twig', [
            'articles' => $articles,
        ]);
    }
    #[Route('/pantheon/article/{id}', name: 'show-article')]
    public function showArticle(int $id, Repository $repository, Article $article): Response
    {
        $article = $repository->find($id);
        if (!$article) {
            throw $this->createNotFoundException('Article not found');
        }
        return $this->render('guest/pantheon/show-article.html.twig', [
            'article' => $article,
        ]);
    }
}

