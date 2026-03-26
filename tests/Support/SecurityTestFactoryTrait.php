<?php

namespace App\Tests\Support;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

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
}
