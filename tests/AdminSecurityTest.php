<?php

namespace App\Tests;

use App\Entity\Annonce;
use App\Entity\Message;
use App\Tests\Support\SecurityTestFactoryTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminSecurityTest extends WebTestCase
{
    use SecurityTestFactoryTrait;

    public function testAnonymousUserIsRedirectedFromAdminDashboard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects('/connexion');
    }

    public function testNonAdminUserCannotAccessAdminDashboard(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createTestUser(['ROLE_USER']), 'main');

        $client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCannotDeleteMessageWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $sender = $this->createTestUser();
        $receiver = $this->createTestUser();
        $message = $this->createTestMessage($sender, $receiver);

        $client->loginUser($admin, 'main');
        $client->request('POST', '/admin/messages/delete/' . $message->getId(), [
            '_token' => 'invalid-csrf',
        ]);

        self::assertResponseRedirects('/admin/messages/list-messages');

        $this->em()->clear();
        $messageInDb = $this->em()->getRepository(Message::class)->find($message->getId());
        self::assertNotNull($messageInDb);
    }

    public function testNonAdminUserCannotDeleteAdminMessage(): void
    {
        $client = static::createClient();
        $user = $this->createTestUser(['ROLE_USER']);

        $client->loginUser($user, 'main');
        $client->request('POST', '/admin/messages/delete/1', [
            '_token' => 'whatever',
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCannotCreateAnnonceWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);

        $client->loginUser($admin, 'main');
        $client->request('POST', '/admin/annonces/create', [
            '_token' => 'invalid-csrf',
            'title' => 'Annonce admin test csrf',
            'description' => 'Description annonce admin test csrf',
            'email' => 'admin@example.com',
            'piece_id' => '1',
        ]);

        self::assertResponseRedirects('/admin/annonces/create');
    }

    public function testAdminCannotUpdateAnnonceWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $annonce = $this->createTestAnnonce($admin);
        $originalTitle = $annonce->getTitle();

        $client->loginUser($admin, 'main');
        $client->request('POST', '/admin/annonces/' . $annonce->getId() . '/update', [
            '_token' => 'invalid-csrf',
            'title' => 'Titre pirate csrf',
            'description' => 'Description modifiee depuis un token invalide.',
            'email' => 'admin@example.com',
            'piece_id' => (string) $annonce->getPiece()->getId(),
            'type' => 'sale',
            'price' => '200',
            'exchange_description' => '',
        ]);

        self::assertResponseRedirects('/admin/annonces/' . $annonce->getId() . '/update');

        $this->em()->clear();
        $annonceInDb = $this->em()->getRepository(Annonce::class)->find($annonce->getId());
        self::assertSame($originalTitle, $annonceInDb?->getTitle());
    }
}
