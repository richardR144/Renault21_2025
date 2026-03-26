<?php

namespace App\Tests;

use App\Entity\Article;
use App\Entity\Piece;
use App\Tests\Support\SecurityTestFactoryTrait;
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
}
