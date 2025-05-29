<?php
namespace App\Controller\Admin;

use App\Entity\Annonce;
use App\Repository\AnnonceRepository;
use App\Repository\PieceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminAnnonceController extends AbstractController
{
    #[Route('/admin/annonces', name: 'admin-annonces-list', methods: ['GET'])]
    public function listAnnonce(AnnonceRepository $annonceRepository): Response
    {
        $annonces = $annonceRepository->findAll();

        return $this->render('admin/annonces/list.html.twig', [
            'annonces' => $annonces
        ]);
    }

    #[Route('/admin/annonces/create', name: 'admin-annonces-create', methods: ['GET', 'POST'])]
    public function createAnnonce(Request $request, EntityManagerInterface $entityManager, PieceRepository $pieceRepository): Response
    {
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');           
            $description = $request->request->get('description');
            $email = $request->request->get('email');
            $imageFile = $request->files->get('image');
            $createdAt = new \DateTimeImmutable();
            $sender = $this->getUser();
            $pieceId = $request->request->get('piece_id');
            $piece = $pieceRepository->find($pieceId);
             
            $annonce = new Annonce();
            
            $annonce->setDescription($description);
            $annonce->setEmail($email = $request->request->get('email'));
            $annonce->setCreatedAt(new \DateTimeImmutable());
            $annonce->setSender($this->getUser());
            $annonce->setPiece($request->request->get('piece'));
            $annonce->setPiece($piece);
            
            /*if (!$imageFile || !$imageFile->isValid()) {
                $this->addFlash('error', 'Image invalide');
                return $this->redirectToRoute('admin-annonces-create');
            } ai-je besoin du côté admin? */

            //je fais ici le traitement de l'image
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('annonces_images_directory'), $newFilename);
                $annonce->setImage($newFilename);
            }

            try {
                $entityManager->persist($annonce);
                $entityManager->flush();
                $this->addFlash('success', 'Annonce créée');
                return $this->redirectToRoute('admin-annonces-list');
            } catch (Exception $exception) {
                $this->addFlash('error', 'Impossible de créer l\'annonce');
            }
        }
        return $this->render('admin/annonces/create.html.twig');
    }

    #[Route('/admin/annonces/delete/{id}', name: 'admin-annonces-delete', methods: ['POST'])]
    public function deleteAnnonce(int $id, AnnonceRepository $annonceRepository, EntityManagerInterface $entityManager): Response
    {
        try {
            $annonce = $annonceRepository->find($id);

            if (!$annonce) {
                throw new Exception('Annonce non trouvée');
            }

            $entityManager->remove($annonce);
            $entityManager->flush();

            $this->addFlash('success', 'Annonce supprimée !');
        } catch (Exception $exception) {
            $this->addFlash('error', 'Impossible de supprimer l\'annonce');
        }

        return $this->redirectToRoute('admin-annonces-list');
    }

    #[Route('/admin/annonces/{id}', name: 'admin-annonces-show', methods: ['GET'])]
    public function showAnnonce(int $id, AnnonceRepository $annonceRepository): Response
    {
        $annonce = $annonceRepository->find($id);
        if (!$annonce) {
            throw $this->createNotFoundException('Annonce non trouvée');
        }
        return $this->render('admin/annonces/show.html.twig', [
            'annonce' => $annonce,
        ]);
    }

}