<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GenderGuesser
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function guess(string $prenom): ?string
    {
        $prenom = $this->client->request(
            'GET',
            'https://api.genderize.io',
            [
                'query' => [
                    'name' => $prenom
                ]
            ]
        );
        $data = $prenom->toArray();
        return $data['gender'] ?? null;  //'gars', 'fille' ou 'inconnu'(non genré)
    }
}
