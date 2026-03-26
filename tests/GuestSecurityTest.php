<?php

namespace App\Tests;

use App\Entity\Piece;
use App\Tests\Support\SecurityTestFactoryTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GuestSecurityTest extends WebTestCase
{
    use SecurityTestFactoryTrait;

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

    public function testAnonymousUserCannotPostDeletePiece(): void
    {
        $client = static::createClient();
        $client->request('POST', '/Guest/pieces/delete-piece/1', [
            '_token' => 'invalid',
        ]);

        self::assertResponseRedirects('/connexion');
    }

    public function testOwnerCannotDeletePieceWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $category = $this->createTestCategory();
        $piece = new Piece();
        $piece->setName('Piece test suppression');
        $piece->setDescription('Description test suppression');
        $piece->setExchange(false);
        $piece->setPrice(120.0);
        $piece->setUser($owner);
        $piece->setCategory($category);
        $this->em()->persist($piece);
        $this->em()->flush();

        $client->loginUser($owner, 'main');
        $client->request('POST', '/Guest/pieces/delete-piece/' . $piece->getId(), [
            '_token' => 'invalid-token',
        ]);

        self::assertResponseRedirects('/Guest/pieces/list-pieces');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->find($piece->getId());
        self::assertNotNull($pieceInDb);
    }

    public function testNonOwnerCannotDeletePiece(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $intruder = $this->createTestUser();
        $category = $this->createTestCategory();

        $piece = new Piece();
        $piece->setName('Piece non proprietaire');
        $piece->setDescription('Description non proprietaire');
        $piece->setExchange(false);
        $piece->setPrice(99.0);
        $piece->setUser($owner);
        $piece->setCategory($category);
        $this->em()->persist($piece);
        $this->em()->flush();

        $client->loginUser($intruder, 'main');
        $client->request('POST', '/Guest/pieces/delete-piece/' . $piece->getId(), [
            '_token' => 'intruder-token',
        ]);

        self::assertResponseRedirects('/Guest/pieces/show-user-piece');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->find($piece->getId());
        self::assertNotNull($pieceInDb);
    }

    public function testUserCanChangePasswordWithValidCurrentPassword(): void
    {
        $client = static::createClient();
        $user = $this->createTestUserWithPassword('OldPass123!');

        $client->loginUser($user, 'main');
        $crawler = $client->request('GET', '/Guest/profil');
        $csrfToken = $crawler->filter('input[name="change_password_form[_token]"]')->attr('value');

        $client->request('POST', '/Guest/profil', [
            'change_password_form' => [
                'currentPassword' => 'OldPass123!',
                'newPassword' => [
                    'first' => 'NewPass456!',
                    'second' => 'NewPass456!',
                ],
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseRedirects('/Guest/profil');

        $this->em()->clear();
        $userInDb = $this->em()->getRepository(\App\Entity\User::class)->find($user->getId());
        self::assertNotNull($userInDb);
        self::assertTrue($this->passwordHasher()->isPasswordValid($userInDb, 'NewPass456!'));
    }

    public function testUserCannotChangePasswordWithInvalidCurrentPassword(): void
    {
        $client = static::createClient();
        $user = $this->createTestUserWithPassword('OldPass123!');

        $client->loginUser($user, 'main');
        $crawler = $client->request('GET', '/Guest/profil');
        $csrfToken = $crawler->filter('input[name="change_password_form[_token]"]')->attr('value');

        $client->request('POST', '/Guest/profil', [
            'change_password_form' => [
                'currentPassword' => 'WrongPass999!',
                'newPassword' => [
                    'first' => 'NewPass456!',
                    'second' => 'NewPass456!',
                ],
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Le mot de passe actuel est incorrect', (string) $client->getResponse()->getContent());

        $this->em()->clear();
        $userInDb = $this->em()->getRepository(\App\Entity\User::class)->find($user->getId());
        self::assertNotNull($userInDb);
        self::assertTrue($this->passwordHasher()->isPasswordValid($userInDb, 'OldPass123!'));
    }

    public function testForgotPasswordPageIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mot-de-passe-oublie');

        self::assertResponseIsSuccessful();
    }

    public function testInvalidResetTokenRedirectsToForgotPassword(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reinitialiser-mot-de-passe/token-invalide');

        self::assertResponseRedirects('/mot-de-passe-oublie');
    }

    public function testUserCanResetPasswordWithValidToken(): void
    {
        $client = static::createClient();
        $user = $this->createTestUserWithPassword('OldPass123!');

        $token = 'token-reset-valide';
        $cache = static::getContainer()->get('cache.app');
        $cacheItem = $cache->getItem('reset_password_' . $token);
        $cacheItem->set($user->getId());
        $cacheItem->expiresAfter(3600);
        $cache->save($cacheItem);

        $crawler = $client->request('GET', '/reinitialiser-mot-de-passe/' . $token);
        $csrfToken = $crawler->filter('input[name="reset_password_form[_token]"]')->attr('value');

        $client->request('POST', '/reinitialiser-mot-de-passe/' . $token, [
            'reset_password_form' => [
                'plainPassword' => [
                    'first' => 'BrandNew789!',
                    'second' => 'BrandNew789!',
                ],
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseRedirects('/connexion');

        $this->em()->clear();
        $userInDb = $this->em()->getRepository(\App\Entity\User::class)->find($user->getId());
        self::assertNotNull($userInDb);
        self::assertTrue($this->passwordHasher()->isPasswordValid($userInDb, 'BrandNew789!'));
    }
}
