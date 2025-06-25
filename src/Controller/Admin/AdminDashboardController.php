<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;

#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController{
    #[Route('/admin', name: 'admin-dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }
    
   #[Route('/admin/categories', name: 'admin-list-categories')]
    public function listCategories(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        return $this->render('admin/categories/list-categories.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/admin/articles', name: 'admin-list-articles')]
    public function listArticles(ArticleRepository $articleRepository): Response
{
    $articles = $articleRepository->findAll();
    return $this->render('admin/article/list-articles.html.twig', [
        'articles' => $articles,
    ]);
}

    #[Route('/admin/messages', name: 'admin-list-messages')]
    public function listMessages(): Response
    {
        // This method can be used to render a list of messages if needed
        return $this->render('admin/messages/list-messages.html.twig');
    }
}
