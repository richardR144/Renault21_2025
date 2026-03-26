<?php

namespace App\Tests;

use App\Entity\Category;
use App\Entity\Article;
use App\Entity\Message;
use App\Entity\Piece;
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
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $owner = $this->createTestUser();
        $category = $this->createTestCategory();
        $piece = new Piece();
        $piece->setName('Piece test suppression');
        $piece->setDescription('Description test suppression');
        $piece->setExchange(false);
        $piece->setPrice(120.0);
        $piece->setUser($owner);
        $piece->setCategory($category);
        $entityManager->persist($piece);
        $entityManager->flush();

        $client->loginUser($owner, 'main');
        $client->request('POST', '/Guest/pieces/delete-piece/' . $piece->getId(), [
            '_token' => 'invalid-token',
        ]);

        self::assertResponseRedirects('/Guest/pieces/list-pieces');

        $entityManager->clear();
        $pieceInDb = $entityManager->getRepository(Piece::class)->find($piece->getId());
        self::assertNotNull($pieceInDb);
    }

    public function testNonOwnerCannotDeletePiece(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

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
        $entityManager->persist($piece);
        $entityManager->flush();

        $client->loginUser($intruder, 'main');

        $client->request('POST', '/Guest/pieces/delete-piece/' . $piece->getId(), [
            '_token' => 'intruder-token',
        ]);

        self::assertResponseRedirects('/Guest/pieces/show-user-piece');

        $entityManager->clear();
        $pieceInDb = $entityManager->getRepository(Piece::class)->find($piece->getId());
        self::assertNotNull($pieceInDb);
    }

    public function testAnonymousUserCannotUpdateMessage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/messages/update/1');

        self::assertResponseRedirects('/connexion');
    }

    public function testNonSenderCannotUpdateMessage(): void
    {
        $client = static::createClient();

        $sender = $this->createTestUser();
        $receiver = $this->createTestUser();
        $intruder = $this->createTestUser();
        $message = $this->createTestMessage($sender, $receiver);

        $client->loginUser($intruder, 'main');
        $client->request('GET', '/messages/update/' . $message->getId());

        self::assertResponseStatusCodeSame(403);
    }

    public function testModeratorCannotUpdateArticleWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $moderator = $this->createTestUser(['ROLE_MODERATOR']);
        $article = $this->createTestArticle();
        $initialTitle = $article->getTitle();

        $client->loginUser($moderator, 'main');
        $client->request('POST', '/moderator/article/' . $article->getId() . '/update', [
            '_token' => 'invalid-csrf',
            'title' => 'Titre modifie invalide',
            'content' => 'Contenu modifie invalide',
        ]);

        self::assertResponseRedirects('/moderator/article/' . $article->getId() . '/update');

        $entityManager->clear();
        $articleInDb = $entityManager->getRepository(Article::class)->find($article->getId());
        self::assertSame($initialTitle, $articleInDb?->getTitle());
    }

    public function testModeratorCannotUpdatePieceWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $moderator = $this->createTestUser(['ROLE_MODERATOR']);
        $owner = $this->createTestUser();
        $category = $this->createTestCategory();

        $piece = new Piece();
        $piece->setName('Piece moderation test');
        $piece->setDescription('Description moderation test');
        $piece->setExchange(false);
        $piece->setPrice(150.0);
        $piece->setUser($owner);
        $piece->setCategory($category);
        $entityManager->persist($piece);
        $entityManager->flush();
        $initialName = $piece->getName();

        $client->loginUser($moderator, 'main');
        $client->request('POST', '/moderator/piece/' . $piece->getId() . '/update', [
            '_token' => 'invalid-csrf',
            'name' => 'Nom invalide',
            'description' => 'Description invalide',
            'price' => '999',
        ]);

        self::assertResponseRedirects('/moderator/piece/' . $piece->getId() . '/update');

        $entityManager->clear();
        $pieceInDb = $entityManager->getRepository(Piece::class)->find($piece->getId());
        self::assertSame($initialName, $pieceInDb?->getName());
    }

    private function createTestUser(array $roles = ['ROLE_USER']): User
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('security-test-' . uniqid() . '@example.com');
        $user->setPseudo('security_test_user');
        $user->setRoles($roles);
        $user->setPassword('dummy');

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function createTestCategory(): Category
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $category = new Category();
        $category->setName('Category test ' . uniqid());
        $category->setDescription('Description category test');
        $category->setImage(null);

        $entityManager->persist($category);
        $entityManager->flush();

        return $category;
    }

    private function createTestMessage(User $sender, User $receiver): Message
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $message = new Message();
        $message->setContent('Message de test');
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setCreatedAt(new \DateTime());
        $message->setIsRead(false);

        $entityManager->persist($message);
        $entityManager->flush();

        return $message;
    }

    private function createTestArticle(): Article
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $article = new Article();
        $article->setTitle('Article test ' . uniqid());
        $article->setContent('Contenu article test');
        $article->setImage(null);

        $entityManager->persist($article);
        $entityManager->flush();

        return $article;
    }
}
