<?php

namespace App\Controller\Guest;
use App\Repository\PieceRepository; 
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;   
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Piece;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class ProfilController extends AbstractController
{
    #[Route('/Guest/profil', name: 'profil')]
    public function profilUser(Request $request, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        return $this->render('guest/profil.html.twig', [
            'user' => $user,
        ]);
    }

   
}