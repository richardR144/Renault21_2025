<?php
namespace App\Controller\guest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class PageController extends AbstractController {
   #[Route('/', name: 'home')]
    public function displayHome(): Response{
       return $this->render('guest/home.html.twig');
    }
}

//dd("Bonjour"); à faire régulièrement