<?php

namespace App\Controller\Guest;

use App\Form\ForgotPasswordRequestForm;
use App\Form\ResetPasswordForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetController extends AbstractController
{
    private const TOKEN_PREFIX = 'reset_password_';
    private const TOKEN_TTL_SECONDS = 3600;

    #[Route('/mot-de-passe-oublie', name: 'forgot-password', methods: ['GET', 'POST'])]
    public function requestReset(
        Request $request,
        UserRepository $userRepository,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        #[Autowire(service: 'cache.app')] CacheItemPoolInterface $cache
    ): Response {
        $form = $this->createForm(ForgotPasswordRequestForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $cacheItem = $cache->getItem(self::TOKEN_PREFIX . $token);
                $cacheItem->set((int) $user->getId());
                $cacheItem->expiresAfter(self::TOKEN_TTL_SECONDS);
                $cache->save($cacheItem);

                $resetUrl = $urlGenerator->generate('reset-password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                try {
                    $mailer->send((new Email())
                        ->to((string) $user->getEmail())
                        ->subject('Reinitialisation de votre mot de passe')
                        ->text("Bonjour,\n\nPour reinitialiser votre mot de passe, cliquez sur ce lien :\n$resetUrl\n\nCe lien expire dans 1 heure.")
                    );
                } catch (\Throwable $e) {
                    // Do not leak mail infrastructure details to the user.
                }
            }

            $this->addFlash('success', 'Si un compte existe pour cet email, un lien de reinitialisation a ete envoye.');
            return $this->redirectToRoute('connexion');
        }

        return $this->render('guest/forgot-password.html.twig', [
            'forgotPasswordForm' => $form->createView(),
        ]);
    }

    #[Route('/reinitialiser-mot-de-passe/{token}', name: 'reset-password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'cache.app')] CacheItemPoolInterface $cache
    ): Response {
        $cacheKey = self::TOKEN_PREFIX . $token;
        $cacheItem = $cache->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            $this->addFlash('error', 'Lien de reinitialisation invalide ou expire.');
            return $this->redirectToRoute('forgot-password');
        }

        $userId = (int) $cacheItem->get();
        $user = $userRepository->find($userId);
        if (!$user) {
            $cache->deleteItem($cacheKey);
            $this->addFlash('error', 'Lien de reinitialisation invalide ou expire.');
            return $this->redirectToRoute('forgot-password');
        }

        $form = $this->createForm(ResetPasswordForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $entityManager->flush();

            $cache->deleteItem($cacheKey);
            $this->addFlash('success', 'Votre mot de passe a ete reinitialise avec succes.');
            return $this->redirectToRoute('connexion');
        }

        return $this->render('guest/reset-password.html.twig', [
            'resetPasswordForm' => $form->createView(),
        ]);
    }
}
