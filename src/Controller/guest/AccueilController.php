<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class AccueilController extends AbstractController {

    #[Route(path: '/', name: 'accueil', methods: ['GET'])]
    public function accueil() {
        return $this->render('accueil.html.twig');
    }
}