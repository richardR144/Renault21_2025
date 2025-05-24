<?php

namespace App\Controller\guest;


    use Doctrine\ORM\EntityManagerInterface;
    use Exception;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
    use Symfony\Component\HttpFoundation\Request;
    use App\Entity\User; // Import the User entity

    class InscriptionController extends AbstractController
    {
        #[Route('/inscription', name: "inscription", methods: ['GET', 'POST'])]
        public function displayInscription(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
        {
            if ($request->isMethod('POST')) {// je vérifie que les données sont envoyés en POST
                
                $pseudo = $request->request->get('pseudo');
                $email = $request->request->get('email');       // je récupère l'email et le mot de passe envoyée par le formulaire
                $password = $request->request->get('password');
                $verifPassword = $request->request->get('verifPassword');

                if ($password !== $verifPassword) { // je vérifie que le mot de passe et la vérification sont identiques
                    $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                    return $this->render('guest/user-inscription.html.twig');
                }

                $user = new User();  //je créais une nouvelle instance de l'entité user

                $passwordHashed = $userPasswordHasher->hashPassword($user, $password);
                //Hash le mot de passe avec le service de Symfony 

                //Méthode 2
                $user->createUser($pseudo, $email, $passwordHashed);
                // Utilise une méthode personnalisée pour initialiser l'admin

                try {   //exo 13, 14
                    $entityManager->persist($user);
                    $entityManager->flush();

                    
                    $this->addFlash('success', 'Profil créé');
                    return $this->redirectToRoute('accueil');

                } catch (Exception $exception) {

                    $this->addFlash('error', 'Impossible de créer l\'admin');

                    // si l'erreur vient de la clé d'unicité, je créé un message flash ciblé
                    if ($exception->getCode() === '1062') {
                        $this->addFlash('error', 'Email déjà pris.');
                    }
                }
                //Affiche le formulaire de création
                return $this->render('guest/user-inscription.html.twig');
            }
            // Affiche le formulaire de création si la méthode n'est pas POST
            return $this->render('guest/user-inscription.html.twig');
        }
    }




    //Méthode 1
                //$user->setPassword($passwordHashed);
                //$user->setEmail($email);
                //$user->setRoles(['ROLE_ADMIN']);