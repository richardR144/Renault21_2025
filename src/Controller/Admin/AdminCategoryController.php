<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminCategoryController extends AbstractController
{
    #[Route('/admin/categories/create-category', name: 'admin-create-category')]
    public function createCategory(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $category = new Category();
            $category->setName($request->request->get('name'));
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie créée avec succès !');
            return $this->redirectToRoute('admin-list-categories');
        }

        return $this->render('admin/categories/create-category.html.twig');
    }

    #[Route('/admin/categories', name: 'admin-list-categories')]
    public function listCategories(CategoryRepository $repository): Response
    {
        $categories = $repository->findAll();
        return $this->render('admin/categories/list-categories.html.twig', [
            'categories' => $categories,
        ]);
    }


    #[Route('/admin/categories/{id}/update-category', name: 'admin-update-category', methods: ['GET', 'POST'])]
    public function updateCategory(int $id, Request $request, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager): Response
{
    $category = $categoryRepository->find($id);
    if (!$category) {
        throw $this->createNotFoundException('Catégorie non trouvée');
    }
    if ($request->isMethod('POST') && $this->isCsrfTokenValid('update_category_' . $category->getId(), $request->request->get('_token'))) {
        $category->setName($request->request->get('name'));
        $entityManager->flush();
        $this->addFlash('success', 'Catégorie modifiée avec succès.');
        return $this->redirectToRoute('admin-list-categories');
    }
    return $this->render('admin/categories/update-category.html.twig', [
        'category' => $category,
    ]);
}

    #[Route('/admin/categories/{id}/delete', name: 'admin-delete-category', methods: ['POST'])]
    public function deleteCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_category_' . $category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès !');
        }
        return $this->redirectToRoute('admin-list-categories');
    }
}