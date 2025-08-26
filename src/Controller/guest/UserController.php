<?php

namespace App\Controller\Guest;

use App\Entity\User;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController{
    #[Route('/guest/user/{id}', name: 'guest-user-show')]
    public function showUser(User $user): Response {
        return new Response('Profil utilisateur : ' . $user->getId());
    }
}