<?php

namespace App\Tests;

use App\Entity\Annonce;
use App\Tests\Support\SecurityTestFactoryTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    public function testOwnerCanUpdateAnnonceWithValidPayload(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $annonce = $this->createTestAnnonce($owner);

        $client->loginUser($owner, 'main');
        $crawler = $client->request('GET', '/Guest/annonces/' . $annonce->getId() . '/update');
        $csrfToken = $crawler->filter('input[name="annonce_type_form[_token]"]')->attr('value');

        $client->request('POST', '/Guest/annonces/' . $annonce->getId() . '/update', [
            'annonce_type_form' => [
                'title' => 'Annonce modifiee par le proprietaire',
                'description' => 'Description modifiee valide pour ce test automatisé.',
                'email' => 'owner-update@example.com',
                'type' => 'sale',
                'price' => '210',
                'piece' => (string) $annonce->getPiece()->getId(),
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseRedirects('/Guest/annonces');

        $this->em()->clear();
        $annonceInDb = $this->em()->getRepository(Annonce::class)->find($annonce->getId());
        self::assertSame('Annonce modifiee par le proprietaire', $annonceInDb?->getTitle());
    }

    public function testOwnerCannotUpdateAnnonceWithInvalidPayload(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $annonce = $this->createTestAnnonce($owner);
        $originalTitle = $annonce->getTitle();

        $client->loginUser($owner, 'main');
        $crawler = $client->request('GET', '/Guest/annonces/' . $annonce->getId() . '/update');
        $csrfToken = $crawler->filter('input[name="annonce_type_form[_token]"]')->attr('value');

        $client->request('POST', '/Guest/annonces/' . $annonce->getId() . '/update', [
            'annonce_type_form' => [
                'title' => 'abc',
                'description' => 'Description modifiee valide pour ce test automatisé.',
                'email' => 'owner-update@example.com',
                'type' => 'sale',
                'price' => '210',
                'piece' => (string) $annonce->getPiece()->getId(),
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'Le formulaire contient des erreurs');

        $this->em()->clear();
        $annonceInDb = $this->em()->getRepository(Annonce::class)->find($annonce->getId());
        self::assertSame($originalTitle, $annonceInDb?->getTitle());
    }

    public function testOwnerCannotCreateAnnonceWithInvalidImageMime(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $piece = $this->createTestPiece($owner);
        $title = 'Annonce upload mime invalide';

        $client->loginUser($owner, 'main');
        $crawler = $client->request('GET', '/Guest/annonces/create');
        $csrfToken = $crawler->filter('input[name="annonce_type_form[_token]"]')->attr('value');

        $tmpFilePath = $this->createTempFileWithSize(1024);
        file_put_contents($tmpFilePath, 'not-an-image-file');
        $file = new UploadedFile($tmpFilePath, 'payload.txt', 'text/plain', null, true);

        $client->request('POST', '/Guest/annonces/create', [
            'annonce_type_form' => [
                'title' => $title,
                'description' => 'Description suffisante pour test upload invalide.',
                'email' => 'owner-upload@example.com',
                'type' => 'sale',
                'price' => '120',
                'piece' => (string) $piece->getId(),
                '_token' => $csrfToken,
            ],
        ], [
            'annonce_type_form' => [
                'image' => $file,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'Le formulaire contient des erreurs');

        $this->em()->clear();
        $annonceInDb = $this->em()->getRepository(Annonce::class)->findOneBy(['title' => $title]);
        self::assertNull($annonceInDb);
    }

    public function testOwnerCannotCreateAnnonceWithOversizedImage(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $piece = $this->createTestPiece($owner);
        $title = 'Annonce upload oversize invalide';

        $client->loginUser($owner, 'main');
        $crawler = $client->request('GET', '/Guest/annonces/create');
        $csrfToken = $crawler->filter('input[name="annonce_type_form[_token]"]')->attr('value');

        $tmpFilePath = $this->createTempFileWithSize(5 * 1024 * 1024 + 1);
        $file = new UploadedFile($tmpFilePath, 'oversize.jpg', 'image/jpeg', null, true);

        $client->request('POST', '/Guest/annonces/create', [
            'annonce_type_form' => [
                'title' => $title,
                'description' => 'Description suffisante pour test upload oversized.',
                'email' => 'owner-upload@example.com',
                'type' => 'sale',
                'price' => '120',
                'piece' => (string) $piece->getId(),
                '_token' => $csrfToken,
            ],
        ], [
            'annonce_type_form' => [
                'image' => $file,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'Le formulaire contient des erreurs');

        $this->em()->clear();
        $annonceInDb = $this->em()->getRepository(Annonce::class)->findOneBy(['title' => $title]);
        self::assertNull($annonceInDb);
    }

    public function testOwnerCannotUpdateAnnonceWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $annonce = $this->createTestAnnonce($owner);
        $originalTitle = $annonce->getTitle();

        $client->loginUser($owner, 'main');
        $client->request('POST', '/Guest/annonces/' . $annonce->getId() . '/update', [
            'annonce_type_form' => [
                'title' => 'Titre modifie csrf invalide',
                'description' => 'Description valide pour un test de csrf invalide.',
                'email' => 'owner-update@example.com',
                'type' => 'sale',
                'price' => '210',
                'piece' => (string) $annonce->getPiece()->getId(),
                '_token' => 'invalid-csrf-token',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'Le formulaire contient des erreurs');

        $this->em()->clear();
        $annonceInDb = $this->em()->getRepository(Annonce::class)->find($annonce->getId());
        self::assertSame($originalTitle, $annonceInDb?->getTitle());
    }

    public function testOwnerCanDeleteAnnonceWithValidCsrfToken(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser();
        $annonce = $this->createTestAnnonce($owner);

        $client->loginUser($owner, 'main');

        $crawler = $client->request('GET', '/Guest/annonces');
        $csrfToken = $crawler
            ->filter('form[action="/Guest/annonces/' . $annonce->getId() . '/delete"] input[name="_token"]')
            ->attr('value');

        $client->request('POST', '/Guest/annonces/' . $annonce->getId() . '/delete', [
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/Guest/annonces');

        $this->em()->clear();
        $annonceInDb = $this->em()->getRepository(Annonce::class)->find($annonce->getId());
        self::assertNull($annonceInDb);
    }

    private function createTempFileWithSize(int $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'annonce_test_');
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
