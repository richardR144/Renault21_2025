<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminPageController extends AbstractController
{
    #[Route('/admin/404', name: 'admin_404')]
    public function displayAdmin404(): Response
    {
        $html = $this->renderView('admin/404.html.twig');

        return new Response($html, '404');
    }

    #[Route('/admin/user-inscription', 'user-inscription')]
    public function displayUserInscription() {
        return $this->render('guest/user-inscription.html.twig');
    }

    #[Route('/admin/user-connexion', 'user-connexion')]
    public function displayUserConnexion(){
        return $this->render('guest/user-connexion.html.twig');
    }
    
    #[Route('/admin/list-pieces', 'list-pieces')]
    public function displayListPiece() {
        return $this->render('admin/list-pieces.html.twig');
    }

    #[Route('admin/details-piece', 'details-piece')]
    public function displayDetailsPiece() {
        return $this->render('admin/details-piece.html.twig');
    }
    #[Route('/admin/list-category', 'list-category')]
    public function displayListCategory() {
        return $this->render('guest/accueil.html.twig');
    }
    /*#[Route('admin/details-category', 'details-category')]
    public function displayDetailsCategory() {
        return $this->render('admin/details-category.html.twig');
    }*/


}