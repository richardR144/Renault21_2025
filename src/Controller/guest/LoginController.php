<?php

namespace App\Controller\guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Response;


// Contrôleur pour la gestion de l'authentification des utilisateurs invités (guest)
class LoginController extends AbstractController
{
    #[Route('/connexion', name: "connexion", methods: ['GET', 'POST'])]
    public function connexionUser(AuthenticationUtils $authenticationUtils): Response {
    $currentUser = $this->getUser();
        if(null !== $currentUser && $currentUser->getRoles() !== ['ROLE_USER']) {
            // Si l'utilisateur est déjà connecté, redirige vers la page d'accueil
            return $this->redirectToRoute('accueil');
        }

        $error = $authenticationUtils->getLastAuthenticationError();


        return $this->render('guest/user-connexion.html.twig', [
            'error' => $error
            
        ]);
    }


    #[Route('/logout', name: "logout", methods: ['GET'])]
    public function logout(){
        // Cette méthode peut rester vide, elle sera interceptée par le firewall de Symfony
    }
}
