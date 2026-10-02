<?php

namespace Database\Seeders;

use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Stock de départ par taille : à ajuster ensuite dans le back office. */
    private const STOCK_DEPART = ['S' => 20, 'M' => 40, 'L' => 40, 'XL' => 20];

    public function run(): void
    {
        $pieces = [
            ['slug' => 'melo-noir', 'nom' => 'Mélo Décalé', 'couleur' => 'Noir', 'image_face' => 'assets/noir-melo-front.webp', 'image_dos' => 'assets/noir-melo-back.webp'],
            ['slug' => 'melo-blanc', 'nom' => 'Mélo Décalé', 'couleur' => 'Blanc', 'image_face' => 'assets/blanc-melo-front.webp', 'image_dos' => 'assets/blanc-melo-back.webp'],
            ['slug' => 'tiakolise-noir', 'nom' => 'Tiakolisé', 'couleur' => 'Noir', 'image_face' => 'assets/noir-tiakolise-front.webp', 'image_dos' => 'assets/noir-tiakolise-back.webp'],
            ['slug' => 'tiakolise-blanc', 'nom' => 'Tiakolisé', 'couleur' => 'Blanc', 'image_face' => 'assets/blanc-tiakolise-front.webp', 'image_dos' => 'assets/blanc-tiakolise-back.webp'],
        ];

        foreach ($pieces as $i => $p) {
            $produit = Produit::updateOrCreate(['slug' => $p['slug']], $p + ['prix' => 12500, 'ordre' => $i]);
            foreach (array_values(Produit::TAILLES) as $j => $taille) {
                $produit->stocks()->firstOrCreate(['taille' => $taille], ['quantite' => self::STOCK_DEPART[$taille], 'ordre' => $j]);
            }
        }

        // Premier compte du back office (ADMIN_EMAIL / ADMIN_PASSWORD dans .env)
        if (env('ADMIN_EMAIL') && env('ADMIN_PASSWORD')) {
            User::firstOrCreate(
                ['email' => env('ADMIN_EMAIL')],
                ['name' => env('ADMIN_NAME', 'Admin'), 'password' => env('ADMIN_PASSWORD')],
            );
        }
    }
}
