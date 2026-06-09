<?php

namespace App\Controller\Moderator;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_MODERATOR')]
class ModeratorDashboardController extends AbstractController
{
    #[Route('/moderator', name: 'moderator-dashboard')]
    public function index(): Response
    {
        return $this->render('moderator/dashboard.html.twig');
    }
}