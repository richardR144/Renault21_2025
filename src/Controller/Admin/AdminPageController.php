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
}