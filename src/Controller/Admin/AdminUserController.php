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

    private function validateUserData(Request $request): array
    {
        $pseudo = trim($request->request->get('pseudo', ''));
        $email = trim($request->request->get('email', ''));
        $password = $request->request->get('password', '');
        $role = $request->request->get('role', 'ROLE_USER');
        $errors = [];

        //VALIDATION PSEUDO
        if (empty($pseudo)) {
            $errors[] = 'Le pseudo est obligatoire';
        } elseif (strlen($pseudo) < 3) {
            $errors[] = 'Le pseudo doit contenir au moins 3 caractères';
        } elseif (strlen($pseudo) > 50) {
            $errors[] = 'Le pseudo ne peut pas dépasser 50 caractères';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $pseudo)) {
            $errors[] = 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores';
        }

        //VALIDATION EMAIL
        if (empty($email)) {
            $errors[] = 'L\'email est obligatoire';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format d\'email invalide';
        } elseif (strlen($email) > 180) {
            $errors[] = 'L\'email ne peut pas dépasser 180 caractères';
        }

        //VALIDATION MOT DE PASSE
        if (empty($password)) {
            $errors[] = 'Le mot de passe est obligatoire';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre';
        }

        //VALIDATION RÔLE (SÉCURITÉ CRITIQUE)
        $allowedRoles = ['ROLE_USER', 'ROLE_MODERATOR', 'ROLE_ADMIN'];
        if (!in_array($role, $allowedRoles)) {
            $errors[] = 'Rôle non autorisé';
        }

        //PROTECTION XSS
        $pseudo = htmlspecialchars($pseudo, ENT_QUOTES, 'UTF-8');

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        return [
            'pseudo' => $pseudo,
            'email' => $email,
            'password' => $password,
            'role' => $role
        ];
    }


    #[Route(path: '/admin/create-user', name: 'admin-create-user', methods: ['GET', 'POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            try {
                //VALIDATION COMPLÈTE
                $validatedData = $this->validateUserData($request);

                //VÉRIFIER EMAIL UNIQUE
                if ($userRepository->findOneBy(['email' => $validatedData['email']])) {
                    throw new \InvalidArgumentException('Cet email est déjà utilisé');
                }

                //VÉRIFIER PSEUDO UNIQUE
                if ($userRepository->findOneBy(['pseudo' => $validatedData['pseudo']])) {
                    throw new \InvalidArgumentException('Ce pseudo est déjà utilisé');
                }

                $user = new User();
                $passwordHashed = $userPasswordHasher->hashPassword($user, $validatedData['password']);

                //SETTERS SÉCURISÉS
                $user->setPseudo($validatedData['pseudo']);
                $user->setEmail($validatedData['email']);
                $user->setPassword($passwordHashed);
                $user->setRoles([$validatedData['role']]);


                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Utilisateur créé avec succès !');
                return $this->redirectToRoute('admin-list-users');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de l\'utilisateur');
            }
        }

        return $this->render('admin/user/create-user.html.twig');
    }

    
    #[Route('/admin/list-users', name: 'admin-list-users')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/user/list-users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route(path: '/admin/delete-user/{id}', name: 'admin-delete-user', methods: ['POST'])]
    public function deleteUser(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        try {
            $user = $userRepository->find($id);

            if (!$user) {
                throw new \Exception('Utilisateur non trouvé');
            }

            //PROTECTION : Empêcher l'auto-suppression
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
                return $this->redirectToRoute('admin-list-users');
            }

            //VÉRIFICATION CSRF
            if ($this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
                $entityManager->remove($user);
                $entityManager->flush();
                $this->addFlash('success', 'Utilisateur supprimé avec succès !');
            } else {
                //GESTION ERREUR CSRF
                $this->addFlash('error', 'Token de sécurité invalide. Suppression annulée.');
            }
        } catch (\Exception $exception) {
            $this->addFlash('error', 'Impossible de supprimer l\'utilisateur');
        }

        return $this->redirectToRoute('admin-list-users');
    }

    #[Route(path: '/admin/confirm-delete-user/{id}', name: 'admin-confirm-delete-user', methods: ['GET'])]
    public function confirmDeleteUser(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('admin-list-users');
        }

        return $this->render('admin/user/delete-user.html.twig', [
            'user' => $user
        ]);
    }


    //Pour éditer l'utilisateur dans le dashboard admin

    #[Route(path: '/admin/edit-user/{id}', name: 'admin-edit-user', methods: ['GET', 'POST'])]
    public function editUser(int $id, UserRepository $userRepository, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('admin-list-users');
        }

        //PROTECTION : Empêcher l'admin de se supprimer ses propres droits
        if ($user === $this->getUser() && $request->isMethod('POST')) {
            $newRole = $request->request->get('role');
            if ($newRole !== 'ROLE_ADMIN') {
                $this->addFlash('error', 'Vous ne pouvez pas retirer vos propres droits administrateur');
                return $this->render('admin/user/edit-user.html.twig', ['user' => $user]);
            }
        }

        if ($request->isMethod('POST')) {
            try {
                $pseudo = trim($request->request->get('pseudo', ''));
                $email = trim($request->request->get('email', ''));
                $newPassword = $request->request->get('password');
                $role = $request->request->get('role', 'ROLE_USER');

                //VALIDATION PSEUDO
                if (empty($pseudo) || strlen($pseudo) < 3 || strlen($pseudo) > 50) {
                    throw new \InvalidArgumentException('Le pseudo doit contenir entre 3 et 50 caractères');
                }

                //VALIDATION EMAIL
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('Email invalide');
                }

                //VÉRIFIER UNICITÉ (sauf pour l'utilisateur actuel)
                $existingEmail = $userRepository->findOneBy(['email' => $email]);
                if ($existingEmail && $existingEmail !== $user) {
                    throw new \InvalidArgumentException('Cet email est déjà utilisé');
                }

                $existingPseudo = $userRepository->findOneBy(['pseudo' => $pseudo]);
                if ($existingPseudo && $existingPseudo !== $user) {
                    throw new \InvalidArgumentException('Ce pseudo est déjà utilisé');
                }

                //VALIDATION RÔLE
                $allowedRoles = ['ROLE_USER', 'ROLE_MODERATOR', 'ROLE_ADMIN'];
                if (!in_array($role, $allowedRoles)) {
                    throw new \InvalidArgumentException('Rôle non autorisé');
                }

                //MISE À JOUR
                $user->setPseudo(htmlspecialchars($pseudo, ENT_QUOTES, 'UTF-8'));
                $user->setEmail($email);
                $user->setRoles([$role]);

                //MOT DE PASSE OPTIONNEL
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 8) {
                        throw new \InvalidArgumentException('Le nouveau mot de passe doit contenir au moins 8 caractères');
                    }
                    $passwordHashed = $userPasswordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($passwordHashed);
                }

                $entityManager->flush();
                $this->addFlash('success', 'Utilisateur modifié avec succès !');
                return $this->redirectToRoute('admin-list-users');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification de l\'utilisateur');
            }
        }

        return $this->render('admin/user/edit-user.html.twig', [
            'user' => $user
        ]);
    }
}
