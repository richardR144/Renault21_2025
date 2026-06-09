<?php

namespace App\Tests;

use App\Entity\Article;
use App\Entity\Piece;
use App\Tests\Support\SecurityTestFactoryTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ModeratorSecurityTest extends WebTestCase
{
    use SecurityTestFactoryTrait;

    public function testAnonymousUserIsRedirectedFromModeratorDashboard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/moderator');

        self::assertResponseRedirects('/connexion');
    }

    public function testLoggedUserWithoutModeratorRoleCannotAccessModeratorDashboard(): void
    {
        $client = static::createClient();

        $user = $this->createTestUser(['ROLE_USER']);

        $client->loginUser($user, 'main');
        $client->request('GET', '/moderator');

        self::assertResponseStatusCodeSame(403);
    }

    public function testLoggedUserWithoutModeratorRoleCannotAccessModeratorArticleUpdatePage(): void
    {
        $client = static::createClient();

        $user = $this->createTestUser(['ROLE_USER']);
        $article = $this->createTestArticle();

        $client->loginUser($user, 'main');
        $client->request('GET', '/moderator/article/' . $article->getId() . '/update');

        self::assertResponseStatusCodeSame(403);
    }

    public function testLoggedUserWithoutModeratorRoleCannotAccessModeratorPieceUpdatePage(): void
    {
        $client = static::createClient();

        $user = $this->createTestUser(['ROLE_USER']);
        $owner = $this->createTestUser();
        $piece = $this->createTestPiece($owner);

        $client->loginUser($user, 'main');
        $client->request('GET', '/moderator/piece/' . $piece->getId() . '/update');

        self::assertResponseStatusCodeSame(403);
    }

    public function testModeratorCannotUpdateArticleWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

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

        $this->em()->clear();
        $articleInDb = $this->em()->getRepository(Article::class)->find($article->getId());
        self::assertSame($initialTitle, $articleInDb?->getTitle());
    }

    public function testModeratorCannotUpdatePieceWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

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
        $this->em()->persist($piece);
        $this->em()->flush();
        $initialName = $piece->getName();

        $client->loginUser($moderator, 'main');
        $client->request('POST', '/moderator/piece/' . $piece->getId() . '/update', [
            '_token' => 'invalid-csrf',
            'name' => 'Nom invalide',
            'description' => 'Description invalide',
            'price' => '999',
        ]);

        self::assertResponseRedirects('/moderator/piece/' . $piece->getId() . '/update');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->find($piece->getId());
        self::assertSame($initialName, $pieceInDb?->getName());
    }

    public function testModeratorCanUpdateArticleWithValidCsrfToken(): void
    {
        $client = static::createClient();

        $moderator = $this->createTestUser(['ROLE_MODERATOR']);
        $article = $this->createTestArticle();
        $newTitle = 'Titre modere valide ' . uniqid();

        $client->loginUser($moderator, 'main');
        $crawler = $client->request('GET', '/moderator/article/' . $article->getId() . '/update');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/moderator/article/' . $article->getId() . '/update', [
            '_token' => $csrfToken,
            'title' => $newTitle,
            'content' => 'Contenu modere valide',
        ]);

        self::assertResponseRedirects('/moderator/articles');

        $this->em()->clear();
        $articleInDb = $this->em()->getRepository(Article::class)->find($article->getId());
        self::assertSame($newTitle, $articleInDb?->getTitle());
    }

    public function testModeratorCanUpdatePieceWithValidCsrfToken(): void
    {
        $client = static::createClient();

        $moderator = $this->createTestUser(['ROLE_MODERATOR']);
        $owner = $this->createTestUser();
        $piece = $this->createTestPiece($owner);
        $newName = 'Piece moderee valide ' . uniqid();

        $client->loginUser($moderator, 'main');
        $crawler = $client->request('GET', '/moderator/piece/' . $piece->getId() . '/update');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/moderator/piece/' . $piece->getId() . '/update', [
            '_token' => $csrfToken,
            'name' => $newName,
            'description' => 'Description piece moderee valide',
            'price' => '210',
            'category-id' => (string) $piece->getCategory()?->getId(),
        ]);

        self::assertResponseRedirects('/moderator/pieces');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->find($piece->getId());
        self::assertSame($newName, $pieceInDb?->getName());
    }

    public function testModeratorCannotUpdatePieceWithEmptyName(): void
    {
        $client = static::createClient();

        $moderator = $this->createTestUser(['ROLE_MODERATOR']);
        $owner = $this->createTestUser();
        $piece = $this->createTestPiece($owner);
        $initialName = $piece->getName();

        $client->loginUser($moderator, 'main');
        $crawler = $client->request('GET', '/moderator/piece/' . $piece->getId() . '/update');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/moderator/piece/' . $piece->getId() . '/update', [
            '_token' => $csrfToken,
            'name' => '',
            'description' => 'Description test nom vide',
            'price' => '130',
            'category-id' => (string) $piece->getCategory()?->getId(),
        ]);

        self::assertResponseRedirects('/moderator/piece/' . $piece->getId() . '/update');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->find($piece->getId());
        self::assertSame($initialName, $pieceInDb?->getName());
    }

    public function testModeratorCannotUpdateArticleWithInvalidImageMime(): void
    {
        $client = static::createClient();

        $moderator = $this->createTestUser(['ROLE_MODERATOR']);
        $article = $this->createTestArticle();
        $initialTitle = $article->getTitle();

        $client->loginUser($moderator, 'main');
        $crawler = $client->request('GET', '/moderator/article/' . $article->getId() . '/update');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $tmpFilePath = $this->createTempFileWithSize(1024);
        file_put_contents($tmpFilePath, 'not-an-image-file');
        $file = new UploadedFile($tmpFilePath, 'payload.txt', 'text/plain', null, true);

        $client->request('POST', '/moderator/article/' . $article->getId() . '/update', [
            '_token' => $csrfToken,
            'title' => 'Titre qui ne doit pas passer',
            'content' => 'Contenu qui ne doit pas passer',
        ], [
            'image' => $file,
        ]);

        self::assertResponseRedirects('/moderator/article/' . $article->getId() . '/update');

        $this->em()->clear();
        $articleInDb = $this->em()->getRepository(Article::class)->find($article->getId());
        self::assertSame($initialTitle, $articleInDb?->getTitle());
    }

    public function testModeratorCannotUpdatePieceWithInvalidImageMime(): void
    {
        $client = static::createClient();

        $moderator = $this->createTestUser(['ROLE_MODERATOR']);
        $owner = $this->createTestUser();
        $piece = $this->createTestPiece($owner);
        $initialName = $piece->getName();

        $client->loginUser($moderator, 'main');
        $crawler = $client->request('GET', '/moderator/piece/' . $piece->getId() . '/update');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $tmpFilePath = $this->createTempFileWithSize(1024);
        file_put_contents($tmpFilePath, 'not-an-image-file');
        $file = new UploadedFile($tmpFilePath, 'payload.txt', 'text/plain', null, true);

        $client->request('POST', '/moderator/piece/' . $piece->getId() . '/update', [
            '_token' => $csrfToken,
            'name' => 'Nom qui ne doit pas passer',
            'description' => 'Description qui ne doit pas passer',
            'price' => '240',
            'category-id' => (string) $piece->getCategory()?->getId(),
        ], [
            'image' => $file,
        ]);

        self::assertResponseRedirects('/moderator/piece/' . $piece->getId() . '/update');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->find($piece->getId());
        self::assertSame($initialName, $pieceInDb?->getName());
    }

    private function createTempFileWithSize(int $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'moderator_test_');
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
