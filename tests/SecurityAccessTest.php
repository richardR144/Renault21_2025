<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityAccessTest extends WebTestCase
{
    public function testLoginPageIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connexion');

        self::assertResponseIsSuccessful();
    }

    public function testAnonymousUserIsRedirectedFromGuestCreatePiece(): void
    {
        $client = static::createClient();
        $client->request('GET', '/Guest/pieces/create-piece');

        self::assertResponseRedirects('/connexion');
    }

    public function testAnonymousUserIsRedirectedFromAdminDashboard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects('/connexion');
    }

    public function testAnonymousUserIsRedirectedFromModeratorDashboard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/moderator');

        self::assertResponseRedirects('/connexion');
    }

    public function testAnonymousUserCannotPostGuestCreatePiece(): void
    {
        $client = static::createClient();
        $client->request('POST', '/Guest/pieces/create-piece', [
            '_token' => 'token-invalide',
            'insert_piece_form' => [
                'name' => 'Aile avant',
                'description' => 'Piece de test',
                'exchange' => 'vente',
                'price' => '150',
            ],
        ]);

        self::assertResponseRedirects('/connexion');
    }

    public function testAnonymousUserCannotPostMessage(): void
    {
        $client = static::createClient();
        $client->request('POST', '/messages/create', [
            'content' => 'message test',
            'receiver_id' => 1,
        ]);

        self::assertResponseRedirects('/connexion');
    }

    public function testLoggedUserCannotSubmitCreatePieceWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createTestUser(), 'main');

        $client->request('POST', '/Guest/pieces/create-piece', [
            '_token' => 'token-invalide',
            'insert_piece_form' => [
                'name' => 'Aile avant test',
                'description' => 'Piece de test pour verifier le CSRF',
                'exchange' => 'vente',
                'price' => '100',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'Token de');
    }

    private function createTestUser(): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('security-test-' . uniqid() . '@example.com');
        $user->setPseudo('security_test_user');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('dummy');

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
