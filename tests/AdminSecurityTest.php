<?php

namespace App\Tests;

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
}
