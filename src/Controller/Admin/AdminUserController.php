<?php
namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AdminUserController extends AbstractController {
    #[Route('/admin/user-inscription', 'user-inscription')]
    public function displayUserInscription() {
        return $this->render('guest/user-inscription.html.twig');
    }
}
