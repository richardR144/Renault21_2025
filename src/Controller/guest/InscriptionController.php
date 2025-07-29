<?php

namespace App\Controller\Guest;

use App\Service\RateLimiterService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User; 

class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: "inscription", methods: ['GET', 'POST'])]
    public function inscriptionUser(Request $request, RateLimiterService $rateLimiterService, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response {   
        
        // Si c'est une requête POST, traiter l'inscription
        if ($request->isMethod('POST')) {
            
            // Vérifier le rate limiting pour les inscriptions
            if (!$rateLimiterService->checkRegistrationAttempts($request)) {
                $this->addFlash('error', 'Trop de tentatives d\'inscription. Réessayez dans 1 heure.');
                return $this->render('guest/user-inscription.html.twig');
            }
            
            // Récupérer les données du formulaire
            $pseudo = $request->request->get('pseudo');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $verifPassword = $request->request->get('verifPassword');

            // Validation des champs obligatoires
            if (!$password || !$email || !$pseudo) {
                $this->addFlash('error', 'Veuillez remplir tous les champs.');
                return $this->render('guest/user-inscription.html.twig');
            }

            // Validation du mot de passe
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{15,}$/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 15 caractères, une majuscule, une minuscule et un caractère spécial.');
                return $this->render('guest/user-inscription.html.twig');
            }
            
            // Vérification de la correspondance des mots de passe
            if ($password !== $verifPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('guest/user-inscription.html.twig');
            }

            // Créer le nouvel utilisateur
            $user = new User();
            $passwordHashed = $userPasswordHasher->hashPassword($user, $password);
            $role = 'ROLE_USER';
            $user->createUser($pseudo, $email, $passwordHashed, $role);

            try {   
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Profil créé avec succès !');
                return $this->redirectToRoute('accueil');

            } catch (Exception $exception) {
                $this->addFlash('error', 'Impossible de créer l\'utilisateur');

                // Si l'erreur vient de la clé d'unicité
                if ($exception->getCode() === '1062') {
                    $this->addFlash('error', 'Email déjà utilisé');
                }
                
                return $this->render('guest/user-inscription.html.twig');
            }
        }

        // Afficher le formulaire d'inscription (GET)
        return $this->render('guest/user-inscription.html.twig');
    }
}