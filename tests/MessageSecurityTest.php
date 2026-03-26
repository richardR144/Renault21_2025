<?php

namespace App\Tests;

use App\Entity\Message;
use App\Tests\Support\SecurityTestFactoryTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MessageSecurityTest extends WebTestCase
{
    use SecurityTestFactoryTrait;

    public function testAnonymousUserCannotPostMessage(): void
    {
        $client = static::createClient();
        $client->request('POST', '/messages/create', [
            'content' => 'message test',
            'receiver_id' => 1,
        ]);

        self::assertResponseRedirects('/connexion');
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

    public function testAnonymousUserCannotDeleteMessage(): void
    {
        $client = static::createClient();
        $client->request('POST', '/messages/delete/1', [
            '_token' => 'invalid',
        ]);

        self::assertResponseRedirects('/connexion');
    }

    public function testUserCannotDeleteMessageIfNotParticipant(): void
    {
        $client = static::createClient();

        $sender = $this->createTestUser();
        $receiver = $this->createTestUser();
        $intruder = $this->createTestUser();
        $message = $this->createTestMessage($sender, $receiver);

        $client->loginUser($intruder, 'main');
        $client->request('POST', '/messages/delete/' . $message->getId(), [
            '_token' => 'intruder-token',
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testSenderCannotDeleteMessageWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $sender = $this->createTestUser();
        $receiver = $this->createTestUser();
        $message = $this->createTestMessage($sender, $receiver);

        $client->loginUser($sender, 'main');
        $client->request('POST', '/messages/delete/' . $message->getId(), [
            '_token' => 'invalid-token',
        ]);

        self::assertResponseRedirects('/guest/messages/list-messages');

        $this->em()->clear();
        $messageInDb = $this->em()->getRepository(Message::class)->find($message->getId());
        self::assertNotNull($messageInDb);
    }
}
