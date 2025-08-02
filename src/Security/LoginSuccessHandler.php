<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $router;
    private $entityManager;

    public function __construct(RouterInterface $router, EntityManagerInterface $entityManager)
    {
        $this->router = $router;
        $this->entityManager = $entityManager;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        /** @var User $user */
        $user = $token->getUser();
        
        //TRACKING CONNEXION
        $user->setLastLoginAt(new \DateTime());
        $this->entityManager->flush();
        
        if (in_array('ROLE_MODERATOR', $user->getRoles())) {
            return new RedirectResponse($this->router->generate('moderator-dashboard'));
        }
       
        return new RedirectResponse($this->router->generate('redirection-apres-login'));
    }
}
