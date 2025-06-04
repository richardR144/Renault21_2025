<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;



class AdminLoginController extends AbstractController
{
    #[Route('/admin-connexion', name: "admin-connexion", methods: ['GET', 'POST'])]
    public function connexionAdmin(AuthenticationUtils $authenticationUtils): Response {
    $currentUser = $this->getUser();
        if(null !== $currentUser && $currentUser->getRoles() !== ['ROLE_USER']) {
            // Si l'utilisateur est déjà connecté, redirige vers la page d'accueil
            return $this->redirectToRoute('admin/admin-accueil');
        }


        $error = $authenticationUtils->getLastAuthenticationError();


        return $this->render('admin/admin-connexion.html.twig', [
            'error' => $error
            
        ]);
    }


    #[Route('/logout', name: "logout", methods: ['GET'])]
    public function logout(){
        // Cette méthode peut rester vide, elle sera interceptée par le firewall de Symfony
    }
}
