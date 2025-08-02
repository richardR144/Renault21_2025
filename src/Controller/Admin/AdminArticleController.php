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
class AdminArticleController extends AbstractController
{
    #[Route('/admin/create-article', name: 'admin-create-article', methods: ['GET', 'POST'])]
    public function createArticle(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();

        if ($request->isMethod('POST')) {
            try {
                //VALIDATION DONNÉES
                $validatedData = $this->validateArticleData($request);
                $article->setTitle($validatedData['title']);
                $article->setContent($validatedData['content']);

                //UPLOAD SÉCURISÉ
                $imageFileName = $this->handleImageUpload($request, $article);
                if ($imageFileName) {
                    $article->setImage($imageFileName);
                }

                //AJOUT METADATA
                $article->setCreatedAt(new \DateTime());
                $article->setAuthor($this->getUser()); // Si tu as cette relation

                $entityManager->persist($article);
                $entityManager->flush();

                $this->addFlash('success', 'Article créé avec succès !');
                return $this->redirectToRoute('admin-list-articles');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\RuntimeException $e) {
                $this->addFlash('error', 'Erreur lors de la création de l\'article');
            }
        }

        return $this->render('admin/article/create-article.html.twig', [
            'article' => $article
        ]);
    }
    #[IsGranted('ROLE_ADMIN', message: 'Accès réservé aux admins')]
    #[Route('/admin/articles', name: 'admin-list-articles')]
    public function listArticles(ArticleRepository $repository): Response
    {
        $articles = $repository->findAll();
        return $this->render('admin/article/list-articles.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[IsGranted('ROLE_ADMIN', message: 'Accès réservé aux admins')]
    #[Route('/admin/article/{id}/update', name: 'admin-update-article')]
    public function updateArticle(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            try {
                //VALIDATION DONNÉES
                $validatedData = $this->validateArticleData($request);
                $article->setTitle($validatedData['title']);
                $article->setContent($validatedData['content']);

                //UPLOAD SÉCURISÉ
                $imageFileName = $this->handleImageUpload($request, $article);
                if ($imageFileName) {
                    // Supprimer ancienne image
                    if ($article->getImage()) {
                        $oldImagePath = $this->getParameter('article_images_directory') . '/' . $article->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $article->setImage($imageFileName);
                }

                //TIMESTAMP MODIFICATION
                $article->setUpdatedAt(new \DateTime());

                $entityManager->flush();

                $this->addFlash('success', 'Article mis à jour avec succès !');
                return $this->redirectToRoute('admin-list-articles');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\RuntimeException $e) {
                $this->addFlash('error', 'Erreur lors de la modification de l\'article');
            }
        }

        return $this->render('admin/article/update-article.html.twig', [
            'article' => $article,
        ]);
    }

    #[IsGranted('ROLE_ADMIN', message: 'Accès réservé aux admins')]
    #[Route('/admin/article/{id}/delete', name: 'admin-delete-article', methods: ['POST'])]
    public function deleteArticle(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_article_' . $article->getId(), $request->request->get('_token'))) {
            //SUPPRIMER L'IMAGE PHYSIQUE
            if ($article->getImage()) {
                $imagePath = $this->getParameter('article_images_directory') . '/' . $article->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash('success', 'Article supprimé avec succès !');
        } else {
            //GESTION ERREUR CSRF
            $this->addFlash('error', 'Token de sécurité invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('admin-list-articles');
    }

    private function validateArticleData(Request $request): array
    {
        $title = trim($request->request->get('title', ''));
        $content = trim($request->request->get('content', ''));
        $errors = [];

        // Validation titre
        if (empty($title)) {
            $errors[] = 'Le titre est obligatoire';
        } elseif (strlen($title) < 5) {
            $errors[] = 'Le titre doit contenir au moins 5 caractères';
        } elseif (strlen($title) > 255) {
            $errors[] = 'Le titre ne peut pas dépasser 255 caractères';
        }

        // Validation contenu
        if (empty($content)) {
            $errors[] = 'Le contenu est obligatoire';
        } elseif (strlen($content) < 20) {
            $errors[] = 'Le contenu doit contenir au moins 20 caractères';
        } elseif (strlen($content) > 10000) {
            $errors[] = 'Le contenu ne peut pas dépasser 10000 caractères';
        }

        // Protection XSS
        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        return ['title' => $title, 'content' => $content];
    }

    //MÉTHODE D'UPLOAD SÉCURISÉ
    private function handleImageUpload(Request $request, Article $article): ?string
    {
        $imageFile = $request->files->get('image');
        if (!$imageFile) {
            return null;
        }

        // Validation type MIME
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageFile->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Format d\'image non autorisé. Formats acceptés : JPEG, PNG, GIF, WebP');
        }

        // Validation taille (5MB max)
        if ($imageFile->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Image trop volumineuse. Taille maximum : 5MB');
        }

        // Validation extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = $imageFile->guessExtension();
        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException('Extension de fichier non autorisée');
        }

        // Génération nom sécurisé
        $imageFileName = uniqid() . '_' . date('Y-m-d') . '.' . $extension;

        try {
            $imageFile->move($this->getParameter('article_images_directory'), $imageFileName);
            return $imageFileName;
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de l\'upload de l\'image');
        }
    }
}
