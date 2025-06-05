<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminPageController extends AbstractController
{
    #[Route('/admin/404', name: 'admin_404')]
    public function admin404(): Response
    {
        $html = $this->renderView('admin/404.html.twig');

        return new Response($html, '404');
    }

    #[Route('/admin/user-inscription', 'user-inscription')]
    public function userInscription() {
        return $this->render('guest/user-inscription.html.twig');
    }

    #[Route('/admin/user-connexion', 'user-connexion')]
    public function userConnexion(){
        return $this->render('guest/user-connexion.html.twig');
    }
    
    #[Route('/admin/list-pieces', 'list-pieces')]
    public function displayListPiece() {
        return $this->render('admin/list-pieces.html.twig');
    }

    #[Route('admin/details-piece', 'details-piece')]
    public function detailsPiece() {
        return $this->render('admin/details-piece.html.twig');
    }

    #[Route('admin/details-category', 'details-category')]
    public function detailsCategory() {
        return $this->render('admin/details-category.html.twig');
    }


}