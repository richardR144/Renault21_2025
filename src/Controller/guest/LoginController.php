<?php

namespace App\Controller\Guest;

use App\Service\RateLimiterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

// Contrôleur pour la gestion de l'authentification des utilisateurs invités (guest)
class LoginController extends AbstractController
{
  #[Route('/connexion', name: "connexion", methods: ['GET', 'POST'])]
    public function connexionUser(AuthenticationUtils $authenticationUtils, RateLimiterService $rateLimiterServiceRequest, Request $request): Response {
    $currentUser = $this->getUser();
    if (null !== $currentUser) {
        // Si l'utilisateur est déjà connecté, redirige vers la page d'accueil
        return $this->redirectToRoute('accueil');
    }

    // Vérifier le rate limiting pour les tentatives de connexion
        if ($request->isMethod('POST')) {
            if (!$rateLimiterServiceRequest->checkLoginAttempts($request)) {
                $this->addFlash('error', 'Trop de tentatives de connexion. Réessayez dans 15 minutes.');
                return $this->render('guest/user-connexion.html.twig', [
                    'last_username' => '',
                    'error' => null,
                ]);
            }
        }

            $error = $authenticationUtils->getLastAuthenticationError();
            $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('Guest/user-connexion.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
    ]);
}


    #[Route('/logout', name: "logout", methods: ['GET'])]
    public function logout(){
        // Cette méthode peut rester vide, elle sera interceptée par le firewall de Symfony
    }
}
