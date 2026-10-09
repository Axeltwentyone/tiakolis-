<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /** Coordonnées valides d'un client de test. */
    protected function client(array $plus = []): array
    {
        return $plus + ['nom' => 'Favor', 'telephone' => '0700235034', 'email' => 'favor@example.test', 'quartier' => 'Palmeraie', 'commune' => 'Cocody'];
    }

    /** Comme le site : la précommande est créée avec la capture du paiement (multipart : « donnees » en JSON + « capture »). */
    protected function precommander(array $articles, array $client = [], bool $avecCapture = true): TestResponse
    {
        $corps = ['donnees' => json_encode($this->client($client) + ['articles' => $articles])];
        if ($avecCapture) {
            $corps['capture'] = UploadedFile::fake()->image('paiement.png', 600, 1200);
        }

        return $this->post('/api/precommandes', $corps, ['Accept' => 'application/json']);
    }
}
