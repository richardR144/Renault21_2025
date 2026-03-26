<?php

namespace App\Tests;

use App\Entity\Annonce;
use App\Tests\Support\SecurityTestFactoryTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AnnonceSecurityTest extends WebTestCase
{
    use SecurityTestFactoryTrait;

    public function testAnonymousUserIsRedirectedFromGuestCreateAnnonce(): void
    {
        $client = static::createClient();
        $client->request('GET', '/Guest/annonces/create');

        self::assertResponseRedirects('/connexion');
    }

    public function testAnonymousUserCannotPostDeleteAnnonce(): void
    {
        $client = static::createClient();
        $client->request('POST', '/Guest/annonces/1/delete', [
            '_token' => 'invalid-token',
        ]);

        self::assertResponseRedirects('/connexion');
    }

    public function testOwnerCannotDeleteAnnonceWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $annonce = $this->createTestAnnonce($owner);

        $client->loginUser($owner, 'main');
        $client->request('POST', '/Guest/annonces/' . $annonce->getId() . '/delete', [
            '_token' => 'invalid-token',
        ]);

        self::assertResponseRedirects('/Guest/annonces');

        $this->em()->clear();
        $annonceInDb = $this->em()->getRepository(Annonce::class)->find($annonce->getId());
        self::assertNotNull($annonceInDb);
    }

    public function testNonOwnerCannotDeleteAnnonce(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $intruder = $this->createTestUser();
        $annonce = $this->createTestAnnonce($owner);

        $client->loginUser($intruder, 'main');
        $client->request('POST', '/Guest/annonces/' . $annonce->getId() . '/delete', [
            '_token' => 'invalid-token',
        ]);

        self::assertResponseRedirects('/Guest/annonces');

        $this->em()->clear();
        $annonceInDb = $this->em()->getRepository(Annonce::class)->find($annonce->getId());
        self::assertNotNull($annonceInDb);
    }

    public function testNonOwnerCannotAccessUpdateAnnonceForm(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $intruder = $this->createTestUser();
        $annonce = $this->createTestAnnonce($owner);

        $client->loginUser($intruder, 'main');
        $client->request('GET', '/Guest/annonces/' . $annonce->getId() . '/update');

        self::assertResponseRedirects('/Guest/annonces');
    }
}
