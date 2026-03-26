<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityAccessTest extends WebTestCase
{
    public function testLoginPageIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connexion');

        self::assertResponseIsSuccessful();
    }

    public function testAnonymousUserIsRedirectedFromGuestCreatePiece(): void
    {
        $client = static::createClient();
        $client->request('GET', '/Guest/pieces/create-piece');

        self::assertResponseRedirects('/connexion');
    }

    public function testAnonymousUserIsRedirectedFromAdminDashboard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects('/connexion');
    }

    public function testAnonymousUserIsRedirectedFromModeratorDashboard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/moderator');

        self::assertResponseRedirects('/connexion');
    }
}
