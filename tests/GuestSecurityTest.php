<?php

namespace App\Tests;

use App\Entity\Piece;
use App\Tests\Support\SecurityTestFactoryTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GuestSecurityTest extends WebTestCase
{
    use SecurityTestFactoryTrait;

    /** @return mixed */
    private function createTypedClient()
    {
        return static::createClient();
    }

    public function testLoginPageIsReachable(): void
    {
        $client = $this->createTypedClient();
        $client->request('GET', '/connexion');

        self::assertResponseIsSuccessful();
    }

    public function testAnonymousUserIsRedirectedFromGuestCreatePiece(): void
    {
        $client = $this->createTypedClient();
        $client->request('GET', '/Guest/pieces/create-piece');

        self::assertResponseRedirects('/connexion');
    }

    public function testAnonymousUserCannotPostGuestCreatePiece(): void
    {
        $client = $this->createTypedClient();
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
        $client = $this->createTypedClient();
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
        $client = $this->createTypedClient();
        $client->request('POST', '/Guest/pieces/delete-piece/1', [
            '_token' => 'invalid',
        ]);

        self::assertResponseRedirects('/connexion');
    }

    public function testOwnerCannotDeletePieceWithInvalidCsrfToken(): void
    {
        $client = $this->createTypedClient();

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
        $client = $this->createTypedClient();

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

    public function testNonOwnerCannotAccessUpdatePieceForm(): void
    {
        $client = $this->createTypedClient();

        $owner = $this->createTestUser();
        $intruder = $this->createTestUser();
        $piece = $this->createTestPiece($owner);

        $client->loginUser($intruder, 'main');
        $client->request('GET', '/Guest/pieces/update-piece/' . $piece->getId());

        self::assertResponseRedirects('/Guest/pieces/show-user-piece');
    }

    public function testLoggedUserCanCreateExchangePieceWithoutPrice(): void
    {
        $client = $this->createTypedClient();

        $owner = $this->createTestUser();
        $category = $this->createTestCategory();
        $pieceName = 'Piece guest echange sans prix ' . uniqid();

        $client->loginUser($owner, 'main');
        $crawler = $client->request('GET', '/Guest/pieces/create-piece');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/Guest/pieces/create-piece', [
            '_token' => $csrfToken,
            'insert_piece_form' => [
                'category' => (string) $category->getId(),
                'name' => $pieceName,
                'description' => 'Description test piece guest echange sans prix',
                'exchange' => 'echange',
                'price' => '',
            ],
        ]);

        self::assertResponseRedirects('/Guest/pieces/list-pieces');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->findOneBy(['name' => $pieceName]);
        self::assertNotNull($pieceInDb);
        self::assertFalse($pieceInDb->isExchange());
        self::assertNull($pieceInDb->getPrice());
    }

    public function testLoggedUserCannotCreateSalePieceWithoutPrice(): void
    {
        $client = $this->createTypedClient();

        $owner = $this->createTestUser();
        $category = $this->createTestCategory();
        $pieceName = 'Piece guest vente sans prix ' . uniqid();

        $client->loginUser($owner, 'main');
        $crawler = $client->request('GET', '/Guest/pieces/create-piece');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/Guest/pieces/create-piece', [
            '_token' => $csrfToken,
            'insert_piece_form' => [
                'category' => (string) $category->getId(),
                'name' => $pieceName,
                'description' => 'Description test piece guest vente sans prix',
                'exchange' => 'vente',
                'price' => '',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'Le prix est obligatoire pour une vente');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->findOneBy(['name' => $pieceName]);
        self::assertNull($pieceInDb);
    }

    public function testLoggedUserCannotCreatePieceWithInvalidImageMime(): void
    {
        $client = $this->createTypedClient();

        $owner = $this->createTestUser();
        $category = $this->createTestCategory();
        $pieceName = 'Piece guest image mime invalide ' . uniqid();

        $client->loginUser($owner, 'main');
        $crawler = $client->request('GET', '/Guest/pieces/create-piece');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $tmpFilePath = $this->createTempFileWithSize(1024);
        file_put_contents($tmpFilePath, 'not-an-image-file');
        $file = new UploadedFile($tmpFilePath, 'payload.txt', 'text/plain', null, true);

        $client->request('POST', '/Guest/pieces/create-piece', [
            '_token' => $csrfToken,
            'insert_piece_form' => [
                'category' => (string) $category->getId(),
                'name' => $pieceName,
                'description' => 'Description test piece guest image invalide',
                'exchange' => 'vente',
                'price' => '120',
            ],
        ], [
            'insert_piece_form' => [
                'image' => $file,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'Format d\'image non autorisé');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->findOneBy(['name' => $pieceName]);
        self::assertNull($pieceInDb);
    }

    public function testUserCanChangePasswordWithValidCurrentPassword(): void
    {
        $client = $this->createTypedClient();
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
        $client = $this->createTypedClient();
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
        $client = $this->createTypedClient();
        $client->request('GET', '/mot-de-passe-oublie');

        self::assertResponseIsSuccessful();
    }

    public function testInvalidResetTokenRedirectsToForgotPassword(): void
    {
        $client = $this->createTypedClient();
        $client->request('GET', '/reinitialiser-mot-de-passe/token-invalide');

        self::assertResponseRedirects('/mot-de-passe-oublie');
    }

    public function testUserCanResetPasswordWithValidToken(): void
    {
        $client = $this->createTypedClient();
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

    public function testSearchPageIsReachableWithoutQuery(): void
    {
        $client = $this->createTypedClient();
        $client->request('GET', '/search');

        self::assertResponseIsSuccessful();
    }

    public function testSearchByQueryReturnsExpectedPieceName(): void
    {
        $client = $this->createTypedClient();

        $owner = $this->createTestUser();
        $category = $this->createTestCategory();

        $piece = new Piece();
        $piece->setName('Radiateur Test Recherche');
        $piece->setDescription('Description de piece pour la recherche avancee');
        $piece->setExchange(true);
        $piece->setPrice(120.0);
        $piece->setUser($owner);
        $piece->setCategory($category);
        $this->em()->persist($piece);
        $this->em()->flush();

        $client->request('GET', '/search?q=Radiateur');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Radiateur Test Recherche', (string) $client->getResponse()->getContent());
    }

    private function createTempFileWithSize(int $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'guest_piece_test_');
        if ($path === false) {
            self::fail('Impossible de creer un fichier temporaire pour le test.');
        }

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            self::fail('Impossible d ouvrir le fichier temporaire pour ecriture.');
        }

        if ($bytes > 0) {
            fwrite($handle, str_repeat('A', $bytes));
        }

        fclose($handle);

        return $path;
    }
}
