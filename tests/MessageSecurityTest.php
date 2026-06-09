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

    public function testUserCannotCreateMessageWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $sender = $this->createTestUser();
        $receiver = $this->createTestUser();

        $client->loginUser($sender, 'main');
        $client->request('POST', '/messages/create', [
            '_token' => 'invalid-csrf',
            'content' => 'Message avec token invalide',
            'receiver_id' => (string) $receiver->getId(),
        ]);

        self::assertResponseRedirects('/messages/create');

        $this->em()->clear();
        $messageInDb = $this->em()->getRepository(Message::class)->findOneBy([
            'sender' => $sender,
            'receiver' => $receiver,
            'content' => 'Message avec token invalide',
        ]);
        self::assertNull($messageInDb);
    }

    public function testUserCanCreateMessageWithValidCsrfToken(): void
    {
        $client = static::createClient();

        $sender = $this->createTestUser();
        $receiver = $this->createTestUser();

        $client->loginUser($sender, 'main');
        $crawler = $client->request('GET', '/messages/create');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $content = 'Message valide ' . uniqid();
        $client->request('POST', '/messages/create', [
            '_token' => $csrfToken,
            'content' => $content,
            'receiver_id' => (string) $receiver->getId(),
        ]);

        self::assertResponseRedirects('/guest/messages/list-messages');

        $this->em()->clear();
        $messageInDb = $this->em()->getRepository(Message::class)->findOneBy([
            'sender' => $sender,
            'receiver' => $receiver,
            'content' => $content,
        ]);
        self::assertNotNull($messageInDb);
    }

    public function testSenderCannotUpdateMessageWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $sender = $this->createTestUser();
        $receiver = $this->createTestUser();
        $message = $this->createTestMessage($sender, $receiver);
        $originalContent = $message->getContent();

        $client->loginUser($sender, 'main');
        $client->request('POST', '/messages/update/' . $message->getId(), [
            '_token' => 'invalid-csrf',
            'content' => 'Nouveau contenu refusé',
        ]);

        self::assertResponseRedirects('/guest/messages/list-messages');

        $this->em()->clear();
        $messageInDb = $this->em()->getRepository(Message::class)->find($message->getId());
        self::assertSame($originalContent, $messageInDb?->getContent());
    }

    public function testSenderCannotUpdateMessageWithEmptyContent(): void
    {
        $client = static::createClient();

        $sender = $this->createTestUser();
        $receiver = $this->createTestUser();
        $message = $this->createTestMessage($sender, $receiver);
        $originalContent = $message->getContent();

        $client->loginUser($sender, 'main');
        $crawler = $client->request('GET', '/messages/update/' . $message->getId());
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/messages/update/' . $message->getId(), [
            '_token' => $csrfToken,
            'content' => '   ',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'Le contenu du message ne peut pas être vide');

        $this->em()->clear();
        $messageInDb = $this->em()->getRepository(Message::class)->find($message->getId());
        self::assertSame($originalContent, $messageInDb?->getContent());
    }

    public function testSenderCanUpdateMessageWithValidPayload(): void
    {
        $client = static::createClient();

        $sender = $this->createTestUser();
        $receiver = $this->createTestUser();
        $message = $this->createTestMessage($sender, $receiver);

        $client->loginUser($sender, 'main');
        $crawler = $client->request('GET', '/messages/update/' . $message->getId());
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $newContent = 'Contenu modifié ' . uniqid();
        $client->request('POST', '/messages/update/' . $message->getId(), [
            '_token' => $csrfToken,
            'content' => $newContent,
        ]);

        self::assertResponseRedirects('/guest/messages/list-messages');

        $this->em()->clear();
        $messageInDb = $this->em()->getRepository(Message::class)->find($message->getId());
        self::assertSame($newContent, $messageInDb?->getContent());
        self::assertNotNull($messageInDb?->getUpdatedAt());
    }
}
