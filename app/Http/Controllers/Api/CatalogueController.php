<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\Statut;
use App\Models\Ligne;
use App\Models\Precommande;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;

class CatalogueController extends Controller
{
    /** Pièces actives, dans l'ordre du scroll, avec le stock restant par taille. */
    public function __invoke(): JsonResponse
    {
        // pièces déjà réservées (hors commandes annulées), pour la jauge « Série limitée »
        $reservees = Ligne::whereHas('precommande', fn ($q) => $q->where('statut', '!=', Statut::Annulee))
            ->selectRaw('produit_id, SUM(quantite) as n')->groupBy('produit_id')->pluck('n', 'produit_id');

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
                'reservees' => (int) ($reservees[$p->id] ?? 0),
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
