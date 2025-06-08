<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminArticleController extends AbstractController {
    #[Route('/admin/create-article', name: 'admin-create-article')]
    public function createArticle(Request $request, EntityManagerInterface $entityManager): Response
     {
        
       $article = new Article();
       
       if ($request->isMethod('POST')) {
           $article->setTitle($request->request->get('title'));
           $article->setContent($request->request->get('content'));

           $imageFile = $request->files->get('image');
           if ($imageFile) {
               $imageFileName = uniqid() . '.' . $imageFile->guessExtension();
               $imageFile->move($this->getParameter('article_images_directory'), $imageFileName);
               $article->setImage($imageFileName);
           }
           
           $entityManager->persist($article);
           $entityManager->flush();
           
           $this->addFlash('success', 'Article created successfully!');
           return $this->redirectToRoute('admin-list-articles');
        }
            return $this->render('admin/article/create-article.html.twig');
        }

        #[Route('/admin/articles', name: 'admin-list-articles')]
        public function listArticles(ArticleRepository $repository): Response
        {
            $articles = $repository->findAll();
            return $this->render('admin/article/list-articles.html.twig', [
                'articles' => $articles,
        ]);
    }
    #[Route('/admin/article/{id}/update', name: 'admin-update-article')]
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

        $this->addFlash('success', 'Article mis à jour avec succès !');
        return $this->redirectToRoute('admin-list-articles');
    }

    return $this->render('admin/article/update-article.html.twig', [
        'article' => $article,
    ]);
}

    #[Route('/admin/article/{id}/delete', name: 'admin-delete-article', methods: ['POST'])]
    public function deleteArticle(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_article_' . $article->getId(), $request->request->get('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash('success', 'Article supprimé avec succès !');
        }
        return $this->redirectToRoute('admin-list-articles');
    }
}


