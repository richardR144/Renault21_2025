<?php

namespace App\Controller\Guest;

use App\Form\ChangePasswordForm;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PieceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\MessageRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfilController extends AbstractController
{
    #[Route('/Guest/profil', name: 'profil')]
    public function profilUser(
        Request $request,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        

        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        $messages = $messageRepository->findBy(['receiver' => $user], ['createdAt' => 'DESC']);

        $changePasswordForm = $this->createForm(ChangePasswordForm::class);
        $changePasswordForm->handleRequest($request);

        if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
            $currentPassword = (string) $changePasswordForm->get('currentPassword')->getData();
            $newPassword = (string) $changePasswordForm->get('newPassword')->getData();

            if ($user->getPassword() !== null && !$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $changePasswordForm->get('currentPassword')->addError(new FormError('Le mot de passe actuel est incorrect.'));
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe mis à jour avec succès.');
                return $this->redirectToRoute('profil');
            }
        }

        return $this->render('guest/profil.html.twig', [
            'user' => $user,
            'messages' => $messages,
            'changePasswordForm' => $changePasswordForm->createView(),
        ]);
    }

    #[Route('/Guest/profil/piece', name: 'profil-piece')]
    public function profilPiece(PieceRepository $pieceRepository): Response
    {
        $currentUser = $this->getUser();
        $pieces = $pieceRepository->findBy(['user' => $currentUser]);

        return $this->render('guest/show-user-piece.html.twig', ['pieces' => $pieces, 'user' => $currentUser]);
    }
}