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
    private function handleImageUpload(Request $request, Category $category): ?string
    {
        $imageFile = $request->files->get('image');
        if (!$imageFile) {
            return null;
        }

        //VALIDATION TYPE MIME
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageFile->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Format d\'image non autorisé. Formats acceptés : JPEG, PNG, GIF, WebP');
        }

        //VALIDATION TAILLE (5MB max)
        if ($imageFile->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Image trop volumineuse. Taille maximum : 5MB');
        }

        //VALIDATION EXTENSION
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = $imageFile->guessExtension();
        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException('Extension de fichier non autorisée');
        }

        //GÉNÉRATION NOM SÉCURISÉ
        $imageFileName = 'category_' . uniqid() . '_' . date('Y-m-d') . '.' . $extension;

        try {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/categories';
            $imageFile->move($uploadDir, $imageFileName);
            return $imageFileName;
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de l\'upload de l\'image');
        }
    }

    private function validateCategoryData(Request $request, CategoryRepository $repository, ?Category $currentCategory = null): array
    {
        $name = trim($request->request->get('name', ''));
        $description = trim($request->request->get('description', ''));
        $errors = [];

        //VALIDATION NOM
        if (empty($name)) {
            $errors[] = 'Le nom est obligatoire';
        } elseif (strlen($name) < 2) {
            $errors[] = 'Le nom doit contenir au moins 2 caractères';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Le nom ne peut pas dépasser 100 caractères';
        }

        //VALIDATION DESCRIPTION
        if (empty($description)) {
            $errors[] = 'La description est obligatoire';
        } elseif (strlen($description) < 10) {
            $errors[] = 'La description doit contenir au moins 10 caractères';
        } elseif (strlen($description) > 500) {
            $errors[] = 'La description ne peut pas dépasser 500 caractères';
        }

        //Vérification unicité nom
        if ($repository) {
            $existingCategory = $repository->findOneBy(['name' => $name]);
            if ($existingCategory && (!$currentCategory || $existingCategory->getId() !== $currentCategory->getId())) {
                $errors[] = 'Une catégorie avec ce nom existe déjà';
            }
        }

        //PROTECTION XSS
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        return ['name' => $name, 'description' => $description];
    }

    #[Route('/admin/categories/create-category', name: 'admin-create-category', methods: ['GET', 'POST'])]
    public function createCategory(Request $request, EntityManagerInterface $entityManager, CategoryRepository $repository): Response
    {
        if ($request->isMethod('POST')) {
            try {
                //VALIDATION DONNÉES
                $validatedData = $this->validateCategoryData($request, $repository);

                $category = new Category();
                $category->setName($validatedData['name']);
                $category->setDescription($validatedData['description']);

                //UPLOAD SÉCURISÉ
                $imageFileName = $this->handleImageUpload($request, $category);
                if ($imageFileName) {
                    $category->setImage($imageFileName);
                }

                $entityManager->persist($category);
                $entityManager->flush();

                $this->addFlash('success', 'Catégorie créée avec succès !');
                return $this->redirectToRoute('admin-list-categories');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\RuntimeException $e) {
                $this->addFlash('error', 'Erreur lors de la création de la catégorie');
            }
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
            $this->addFlash('error', 'Catégorie introuvable');
            return $this->redirectToRoute('admin-list-categories');
}

        if ($request->isMethod('POST')) {
            try {
                //VALIDATION DONNÉES
                $validatedData = $this->validateCategoryData($request, $categoryRepository, $category);
                $category->setName($validatedData['name']);
                $category->setDescription($validatedData['description']);

                //UPLOAD SÉCURISÉ
                $imageFileName = $this->handleImageUpload($request, $category);
                if ($imageFileName) {
                    //SUPPRIMER ANCIENNE IMAGE
                    if ($category->getImage()) {
                        $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/categories/' . $category->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $category->setImage($imageFileName);
                }

                $entityManager->flush();
                $this->addFlash('success', 'Catégorie modifiée avec succès !');
                return $this->redirectToRoute('admin-list-categories');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\RuntimeException $e) {
                $this->addFlash('error', 'Erreur lors de la modification de la catégorie');
            }
        }

        return $this->render('admin/categories/update-category.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/admin/categories/{id}/delete', name: 'admin-delete-category', methods: ['POST'])]
    public function deleteCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_category_' . $category->getId(), $request->request->get('_token'))) {
            //SUPPRIMER L'IMAGE PHYSIQUE
            if ($category->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/categories/' . $category->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès !');
        } else {
            //GESTION ERREUR CSRF
            $this->addFlash('error', 'Token de sécurité invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('admin-list-categories');
    }
}
