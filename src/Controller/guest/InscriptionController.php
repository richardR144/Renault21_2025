<?php

namespace App\Controller\guest;


    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

    class InscriptionController extends AbstractController
    {
        #[Route('/inscription', name: "inscription", methods: ['GET', 'POST'])]
        public function displayInscription(AuthenticationUtils $authenticationUtils): Response {
            $error = $authenticationUtils->getLastAuthenticationError();
            return $this->render('guest/user-inscription.html.twig', [
                'error' => $error
            ]);
        }
    }