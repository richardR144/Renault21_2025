<?php

namespace App\Controller\Guest;


use App\Entity\User;
use App\Service\GenderGuesser;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class UserController extends AbstractController
{
    #[Route('/guest/user/{id}', name: 'guest-user-show', methods: ['GET'])]
    public function showUser(User $user, GenderGuesser $genderGuesser): Response
    {
        // On suppose que le prénom est dans le pseudo (ou adapte selon ton entité)
        $prenom = $user->getPseudo();
        $genre = $genderGuesser->guess($prenom);

        return $this->render('guest/user-show.html.twig', [
            'user' => $user,
            'genre' => $genre,
        ]);
    }
}