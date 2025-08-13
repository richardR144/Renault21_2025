<?php

namespace App\Controller\Guest;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FacebookController extends AbstractController
{
    #[Route('/connect/facebook', name: 'connect_facebook_start')]
    public function connectFacebook(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
        ->getClient('facebook')
        ->redirect(['email', 'profile'], [], 'connect_facebook_check');
    }

    #[Route('/connect/facebook/check', name: 'connect_facebook_check')]
    public function connectFacebookCheck()
    {
        // Géré par le firewall, rien à mettre ici
    }
}
