<?php

namespace App\Tests;

use App\Entity\Annonce;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Message;
use App\Entity\Piece;
use App\Tests\Support\SecurityTestFactoryTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    public function testAdminCannotCreateArticleWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $title = 'Article csrf invalide ' . uniqid();

        $client->loginUser($admin, 'main');
        $client->request('POST', '/admin/create-article', [
            '_token' => 'invalid-csrf',
            'title' => $title,
            'content' => 'Contenu de test suffisamment long pour passer la validation.',
        ]);

        self::assertResponseRedirects('/admin/create-article');

        $this->em()->clear();
        $articleInDb = $this->em()->getRepository(Article::class)->findOneBy(['title' => $title]);
        self::assertNull($articleInDb);
    }

    public function testAdminCannotUpdateArticleWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $article = $this->createTestArticle();
        $originalTitle = $article->getTitle();

        $client->loginUser($admin, 'main');
        $client->request('POST', '/admin/article/' . $article->getId() . '/update', [
            '_token' => 'invalid-csrf',
            'title' => 'Titre modifie csrf invalide',
            'content' => 'Contenu modifie suffisamment long pour le test de sécurité.',
        ]);

        self::assertResponseRedirects('/admin/article/' . $article->getId() . '/update');

        $this->em()->clear();
        $articleInDb = $this->em()->getRepository(Article::class)->find($article->getId());
        self::assertSame($originalTitle, $articleInDb?->getTitle());
    }

    public function testAdminCannotCreateCategoryWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $name = 'Category csrf invalide ' . uniqid();
        $initialCount = count($this->em()->getRepository(Category::class)->findAll());

        $client->loginUser($admin, 'main');
        $client->request('POST', '/admin/categories/create-category', [
            '_token' => 'invalid-csrf',
            'name' => $name,
            'description' => 'Description valide pour test csrf category.',
        ]);

        self::assertResponseRedirects('/admin/categories/create-category');

        $this->em()->clear();
        $finalCount = count($this->em()->getRepository(Category::class)->findAll());
        self::assertSame($initialCount, $finalCount);
    }

    public function testAdminCannotUpdateCategoryWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $category = $this->createTestCategory();
        $originalName = $category->getName();

        $client->loginUser($admin, 'main');
        $client->request('POST', '/admin/categories/' . $category->getId() . '/update-category', [
            '_token' => 'invalid-csrf',
            'name' => 'Nom modifie csrf invalide',
            'description' => 'Description modifiee pour test csrf invalide.',
        ]);

        self::assertResponseRedirects('/admin/categories/' . $category->getId() . '/update-category');

        $this->em()->clear();
        $categoryInDb = $this->em()->getRepository(Category::class)->find($category->getId());
        self::assertSame($originalName, $categoryInDb?->getName());
    }

    public function testAdminCannotCreatePieceWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $category = $this->createTestCategory();
        $pieceName = 'Piece csrf invalide ' . uniqid();

        $client->loginUser($admin, 'main');
        $client->request('POST', '/admin/create-piece', [
            '_token' => 'invalid-csrf',
            'name' => $pieceName,
            'description' => 'Description test csrf piece',
            'exchange' => '1',
            'price' => '120',
            'categoryId' => (string) $category->getId(),
        ]);

        self::assertResponseRedirects('/admin/create-piece');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->findOneBy(['name' => $pieceName]);
        self::assertNull($pieceInDb);
    }

    public function testAdminCannotUpdatePieceWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $piece = $this->createTestPiece($admin);
        $originalName = $piece->getName();

        $client->loginUser($admin, 'main');
        $client->request('POST', '/admin/update-piece/' . $piece->getId(), [
            '_token' => 'invalid-csrf',
            'name' => 'Nom piece modifie csrf invalide',
            'description' => 'Description modifiee csrf invalide',
            'exchange' => '1',
            'price' => '130',
            'categoryId' => (string) $piece->getCategory()?->getId(),
        ]);

        self::assertResponseRedirects('/admin/update-piece/' . $piece->getId());

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->find($piece->getId());
        self::assertSame($originalName, $pieceInDb?->getName());
    }

    public function testAdminCanCreateExchangePieceWithoutPrice(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $category = $this->createTestCategory();
        $pieceName = 'Piece echange sans prix ' . uniqid();

        $client->loginUser($admin, 'main');
        $crawler = $client->request('GET', '/admin/create-piece');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/create-piece', [
            '_token' => $csrfToken,
            'name' => $pieceName,
            'description' => 'Description test piece echange sans prix',
            'exchange' => '0',
            'price' => '',
            'categoryId' => (string) $category->getId(),
        ]);

        self::assertResponseRedirects('/admin/list-pieces');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->findOneBy(['name' => $pieceName]);
        self::assertNotNull($pieceInDb);
        self::assertFalse($pieceInDb->isExchange());
        self::assertNull($pieceInDb->getPrice());
    }

    public function testAdminCannotCreateSalePieceWithoutPrice(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $category = $this->createTestCategory();
        $pieceName = 'Piece vente sans prix ' . uniqid();

        $client->loginUser($admin, 'main');
        $crawler = $client->request('GET', '/admin/create-piece');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/create-piece', [
            '_token' => $csrfToken,
            'name' => $pieceName,
            'description' => 'Description test piece vente sans prix',
            'exchange' => '1',
            'price' => '',
            'categoryId' => (string) $category->getId(),
        ]);

        self::assertResponseRedirects('/admin/create-piece');

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->findOneBy(['name' => $pieceName]);
        self::assertNull($pieceInDb);
    }

    public function testAdminCanCreateAnnonceWithDescriptionLongerThanOneThousandCharacters(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $piece = $this->createTestPiece($admin);
        $title = 'Annonce admin longue ' . uniqid();
        $description = str_repeat('A', 1500);

        $client->loginUser($admin, 'main');
        $crawler = $client->request('GET', '/admin/annonces/create');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/annonces/create', [
            '_token' => $csrfToken,
            'title' => $title,
            'description' => $description,
            'email' => 'admin-long-description@example.com',
            'piece_id' => (string) $piece->getId(),
        ]);

        self::assertResponseRedirects('/admin/annonces');

        $this->em()->clear();
        $annonceInDb = $this->em()->getRepository(Annonce::class)->findOneBy(['title' => $title]);
        self::assertNotNull($annonceInDb);
        self::assertSame($description, $annonceInDb?->getDescription());
    }

    public function testAdminCannotCreatePieceWithInvalidImageMime(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $category = $this->createTestCategory();
        $pieceName = 'Piece image mime invalide ' . uniqid();

        $client->loginUser($admin, 'main');
        $crawler = $client->request('GET', '/admin/create-piece');
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $tmpFilePath = $this->createTempFileWithSize(1024);
        file_put_contents($tmpFilePath, 'not-an-image-file');
        $file = new UploadedFile($tmpFilePath, 'payload.txt', 'text/plain', null, true);

        $client->request('POST', '/admin/create-piece', [
            '_token' => $csrfToken,
            'name' => $pieceName,
            'description' => 'Description piece image mime invalide',
            'exchange' => '1',
            'price' => '120',
            'categoryId' => (string) $category->getId(),
        ], [
            'image' => $file,
        ]);

        self::assertResponseIsSuccessful();

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->findOneBy(['name' => $pieceName]);
        self::assertNull($pieceInDb);
    }

    public function testAdminCannotUpdatePieceWithInvalidImageMime(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $piece = $this->createTestPiece($admin);
        $originalName = $piece->getName();

        $client->loginUser($admin, 'main');
        $crawler = $client->request('GET', '/admin/update-piece/' . $piece->getId());
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $tmpFilePath = $this->createTempFileWithSize(1024);
        file_put_contents($tmpFilePath, 'not-an-image-file');
        $file = new UploadedFile($tmpFilePath, 'payload.txt', 'text/plain', null, true);

        $client->request('POST', '/admin/update-piece/' . $piece->getId(), [
            '_token' => $csrfToken,
            'name' => 'Nom piece non mis a jour',
            'description' => 'Description piece non mise a jour a cause du mime invalide',
            'exchange' => '1',
            'price' => '130',
            'categoryId' => (string) $piece->getCategory()?->getId(),
        ], [
            'image' => $file,
        ]);

        self::assertResponseIsSuccessful();

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->find($piece->getId());
        self::assertSame($originalName, $pieceInDb?->getName());
    }

    public function testAdminCanUpdatePieceFromSaleToExchangeWithoutPrice(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $piece = $this->createTestPiece($admin);
        $piece->setExchange(true);
        $piece->setPrice(150.0);
        $this->em()->flush();

        $client->loginUser($admin, 'main');
        $crawler = $client->request('GET', '/admin/update-piece/' . $piece->getId());
        $csrfToken = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/admin/update-piece/' . $piece->getId(), [
            '_token' => $csrfToken,
            'name' => 'Piece basculee en echange',
            'description' => 'Description mise a jour pour une piece basculee en echange',
            'exchange' => '0',
            'price' => '',
            'categoryId' => (string) $piece->getCategory()?->getId(),
        ]);

        self::assertResponseIsSuccessful();

        $this->em()->clear();
        $pieceInDb = $this->em()->getRepository(Piece::class)->find($piece->getId());
        self::assertSame('Piece basculee en echange', $pieceInDb?->getName());
        self::assertFalse($pieceInDb->isExchange());
        self::assertNull($pieceInDb->getPrice());
    }

    private function createTempFileWithSize(int $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'admin_piece_test_');
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
