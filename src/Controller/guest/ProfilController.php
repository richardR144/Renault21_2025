<?php

namespace App\Controller\Guest;

use App\Repository\PieceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;   
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Repository\MessageRepository;

class ProfilController extends AbstractController
{
    #[Route('/Guest/profil', name: 'profil')]
    public function profilUser(Request $request, UserRepository $userRepository, MessageRepository $messageRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        

        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        $messages = $messageRepository->findBy(['receiver' => $user], ['createdAt' => 'DESC']);

        return $this->render('guest/profil.html.twig', [
            'user' => $user,
            'messages' => $messages,
        ]);
    }

    #[Route('/Guest/profil/piece', name: 'profil-piece')]
    public function profilPiece(Request $request, UserRepository $userRepository, PieceRepository $pieceRepository): Response
    {
        $currentUser = $this->getUser();
        $pieces = [];
        foreach ($pieceRepository->findAll() as $piece) {
            if ($piece->getUser() === $currentUser) {
                $pieces[] = $piece;
            }
        }

        return $this->render('guest/show-user-piece.html.twig', ['pieces' => $pieces, 'user' => $currentUser]);
    }
}