<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ArticleRepository;


#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController{
    #[Route('/admin', name: 'admin-dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/articles', name: 'admin-list-articles')]
    public function listArticles(ArticleRepository $articleRepository): Response
{
    $articles = $articleRepository->findAll();
    return $this->render('admin/article/list-articles.html.twig', [
        'articles' => $articles,
    ]);
}

}
