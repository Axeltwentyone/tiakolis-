<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Precommande;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;

class CatalogueController extends Controller
{
    /** Pièces actives, dans l'ordre du scroll, avec le stock restant par taille. */
    public function __invoke(): JsonResponse
    {
        $pieces = Produit::where('actif', true)->orderBy('ordre')->with('stocks')->get()
            ->map(fn (Produit $p) => [
                'id' => $p->slug,
                'type' => $p->type,
                'nom' => $p->nom,
                'couleur' => $p->couleur,
                'prix' => $p->prix,
                'face' => Produit::urlImage($p->image_face),
                'dos' => Produit::urlImage($p->image_dos),
                'stock' => $p->stocks->pluck('quantite', 'taille'),
            ]);

        $c = config('services.precommandes');

        return response()->json([
            'tailles' => Produit::TAILLES,
            'pieces' => $pieces,
            'communes' => Precommande::COMMUNES,
            'paiement' => ['wave' => $c['wave_numero'], 'lien' => $c['wave_lien'] ?: null, 'whatsapp' => $c['whatsapp_numero']],
        ])
            ->header('Cache-Control', 'no-store'); // le stock bouge : jamais en cache
    }
}
