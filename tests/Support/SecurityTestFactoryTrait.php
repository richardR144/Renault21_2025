<?php

namespace App\Tests\Support;

use App\Entity\Annonce;
use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Message;
use App\Entity\Piece;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait SecurityTestFactoryTrait
{
    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    protected function createTestUser(array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail('security-test-' . uniqid() . '@example.com');
        $user->setPseudo('security_test_user');
        $user->setRoles($roles);
        $user->setPassword('dummy');

        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    protected function passwordHasher(): UserPasswordHasherInterface
    {
        return static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    protected function createTestUserWithPassword(string $plainPassword, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail('security-test-' . uniqid() . '@example.com');
        $user->setPseudo('security_test_user_' . uniqid());
        $user->setRoles($roles);
        $user->setPassword($this->passwordHasher()->hashPassword($user, $plainPassword));

        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    protected function createTestCategory(): Category
    {
        $category = new Category();
        $category->setName('Category test ' . uniqid());
        $category->setDescription('Description category test');
        $category->setImage(null);

        $this->em()->persist($category);
        $this->em()->flush();

        return $category;
    }

    protected function createTestMessage(User $sender, User $receiver): Message
    {
        $message = new Message();
        $message->setContent('Message de test');
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setCreatedAt(new \DateTime());
        $message->setIsRead(false);

        $this->em()->persist($message);
        $this->em()->flush();

        return $message;
    }

    protected function createTestArticle(): Article
    {
        $article = new Article();
        $article->setTitle('Article test ' . uniqid());
        $article->setContent('Contenu article test');
        $article->setImage(null);

        $this->em()->persist($article);
        $this->em()->flush();

        return $article;
    }

    protected function createTestPiece(User $owner): Piece
    {
        $piece = new Piece();
        $piece->setName('Piece test ' . uniqid());
        $piece->setDescription('Description piece test');
        $piece->setExchange(false);
        $piece->setPrice(120.0);
        $piece->setUser($owner);
        $piece->setCategory($this->createTestCategory());

        $this->em()->persist($piece);
        $this->em()->flush();

        return $piece;
    }

    protected function createTestAnnonce(User $sender): Annonce
    {
        $annonce = new Annonce();
        $annonce->setTitle('Annonce test ' . uniqid());
        $annonce->setDescription('Description annonce test');
        $annonce->setEmail('contact+' . uniqid() . '@example.com');
        $annonce->setType('sale');
        $annonce->setPrice(199.99);
        $annonce->setSender($sender);
        $annonce->setPiece($this->createTestPiece($sender));
        $annonce->setCreatedAt(new \DateTimeImmutable());

        $this->em()->persist($annonce);
        $this->em()->flush();

        return $annonce;
    }
}
