<?php

namespace App\Controller\Guest;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect-google-start')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], [], 'connect-google-check');
    }

    #[Route('/connect/google/check', name: 'connect-google-check')]
    public function connectCheckAction(Request $request): Response
    {
        //sera géré par l'authenticator, hahaha
        return new Response('Authentification en cours...');
    }
}