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
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route(path: '/admin/create-user', name: 'admin-create-user', methods: ['GET', 'POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {

        if ($request->isMethod('POST')) { 
            $pseudo = $request->request->get('pseudo');      // je vérifie que les données sont envoyés en POST
            $email = $request->request->get('email');       // je récupère l'email et le mot de passe envoyée par le formulaire
            $password = $request->request->get('password');
            $role = $request->request->get('role', 'ROLE_USER'); 
    
            $user = new User();

            $passwordHashed = $userPasswordHasher->hashPassword($user, $password);


            
            $user->createUser($pseudo, $email, $passwordHashed, $role); // Utilise une méthode personnalisée pour initialiser l'utilisateur
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
    public function listAdmins(UserRepository $userRepository): Response
    {

        $users = $userRepository->findAll();

        return $this->render('admin/user/list-users.html.twig', [
            'users' => $users
        ]);
    }

    #[Route(path: '/admin/delete-user/{id}', name: 'admin-delete-user', methods: ['GET'])]
    public function deleteUser(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        try {
            $user = $userRepository->find($id);

            if (!$user) {
                throw new Exception('Utilisateur non trouvé');
            }

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé !');

        } catch (Exception $exception) {
            $this->addFlash('error', 'Impossible de supprimer l\'utilisateur');
        }

        return $this->redirectToRoute('admin-list-admins');
    }


//Pour éditer l'utilisateur dans le dashboard admin
    #[Route(path: '/admin/edit-user/{id}', name: 'admin-edit-user', methods: ['GET', 'POST'])]
    public function editUser(int $id, UserRepository $userRepository, Request $request, EntityManagerInterface $entityManager): Response
{
    $user = $userRepository->find($id);

    if (!$user) {
        $this->addFlash('error', 'Utilisateur non trouvé');
        return $this->redirectToRoute('admin-list-admins');
    }

    return $this->render('admin/user/edit-user.html.twig', [
        'user' => $user
    ]);
}

}
