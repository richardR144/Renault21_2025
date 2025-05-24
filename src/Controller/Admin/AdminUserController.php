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


class AdminUserController extends AbstractController
{
    #[Route(path: '/admin/create-user', name: 'admin-create-user', methods: ['GET', 'POST'])]
    public function displayCreateUser(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {

        if ($request->isMethod('POST')) {                    // je vérifie que les données sont envoyés en POST
            $email = $request->request->get('email');       // je récupère l'email et le mot de passe envoyée par le formulaire
            $password = $request->request->get('password');

            $user = new User();

            $passwordHashed = $userPasswordHasher->hashPassword($user, $password);


            //Méthode 2
            $user->createUser($email, $passwordHashed);
            // Utilise une méthode personnalisée pour initialiser l'admin

            try {
                $entityManager->persist($user);
                $entityManager->flush();
                
                $this->addFlash('success', 'Utilisateur créé');
                return $this->redirectToRoute('admin-list-admins');

            } catch (Exception $exception) {

                $this->addFlash('error', 'Impossible de créer l\'admin');

                // si l'erreur vient de la clé d'unicité, je créé un message flash ciblé
                if ($exception->getCode() === '1062') {
                    $this->addFlash('error', 'Email déjà pris.');
                }
            }
            //Affiche le formulaire de création
            return $this->render('admin/user/create-user.html.twig');
        }
        // Affiche le formulaire de création si la méthode n'est pas POST
        return $this->render('admin/user/create-user.html.twig');
    }

    #[Route(path: '/admin/list-admins', name: 'admin-list-admins', methods: ['GET'])]
    public function displayListAdmins(UserRepository $userRepository): Response
    {

        $users = $userRepository->findAll();

        return $this->render('/admin/user/list-users.html.twig', [
            'users' => $users
        ]);
    }
}
