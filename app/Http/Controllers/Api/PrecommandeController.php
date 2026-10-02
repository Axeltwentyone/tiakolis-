<?php

namespace App\Http\Controllers\Api;

use App\Enums\Statut;
use App\Http\Controllers\Controller;
use App\Mail\CaptureRecue;
use App\Mail\NouvellePrecommande;
use App\Mail\PrecommandeRecue;
use App\Models\Precommande;
use App\Models\Produit;
use App\Models\Stock;
use App\Support\Courrier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Parcours du tiroir panier :
 *   1. POST /api/precommandes                       → crée la commande, réserve le stock, renvoie référence + jeton
 *   2. PUT  /api/precommandes/{reference}           → « Modifier mes coordonnées » (tant qu'aucune capture n'est envoyée)
 *   3. POST /api/precommandes/{reference}/capture   → capture du paiement Wave, visible dans le back office
 */
class PrecommandeController extends Controller
{
    private const QUANTITE_MAX = 5;   // par ligne

    private const LIGNES_MAX = 10;

    private const LIMITE = 5;          // commandes créées par IP…

    private const FENETRE = 10 * 60;   // …sur 10 minutes

    public function store(Request $request): JsonResponse
    {
        // pot de miel rempli = robot : on répond OK sans rien enregistrer
        if ($request->filled('website')) {
            return response()->json(['reference' => 'TEF-AAAAA', 'jeton' => Str::random(40), 'total' => 0], 201);
        }

        $cle = 'precommande:'.$request->ip();
        if (RateLimiter::tooManyAttempts($cle, self::LIMITE)) {
            return $this->erreur('Trop de commandes. Réessaie dans quelques minutes.', 429);
        }

        [$client, $demande, $erreur] = $this->valider($request);
        if ($erreur) {
            return $erreur;
        }

        $jeton = Str::random(40);
        try {
            $p = DB::transaction(function () use ($client, $demande, $jeton) {
                $p = Precommande::create($client + [
                    'reference' => Precommande::nouvelleReference(),
                    'jeton' => hash('sha256', $jeton),
                    'total' => 0,
                ]);
                $this->reserver($p, $demande);

                return $p;
            });
        } catch (RuntimeException $e) {
            return $this->erreur($e->getMessage(), 409);
        }

        RateLimiter::hit($cle, self::FENETRE);
        if ($p->email) {
            Courrier::envoyer($p->email, new PrecommandeRecue($p), $p->reference);
        }
        Courrier::envoyerEquipe(new NouvellePrecommande($p), $p->reference);

        return response()->json($this->resume($p) + ['jeton' => $jeton], 201);
    }

    /** « Modifier mes coordonnées » / panier modifié après coup : la commande est mise à jour, pas dupliquée. */
    public function update(Request $request, string $reference): JsonResponse
    {
        $p = Precommande::where('reference', $reference)->first();
        if (! $p || ! $p->jetonValide($request->header('X-Jeton'))) {
            return $this->erreur('Commande introuvable.', 404);
        }
        if (! $p->modifiable()) {
            return $this->erreur('Cette commande ne peut plus être modifiée.', 409);
        }

        [$client, $demande, $erreur] = $this->valider($request);
        if ($erreur) {
            return $erreur;
        }

        try {
            DB::transaction(function () use ($p, $client, $demande) {
                $p->ajusterStock(+1);   // rend les pièces réservées…
                $p->lignes()->delete();
                $p->update($client);
                $this->reserver($p, $demande); // …et réserve le nouveau panier
            });
        } catch (RuntimeException $e) {
            return $this->erreur($e->getMessage(), 409);
        }

        return response()->json($this->resume($p->refresh()));
    }

    /** Capture d'écran du paiement Wave : rangée sur le disque privé, l'équipe reçoit un e-mail avec la capture. */
    public function capture(Request $request, string $reference): JsonResponse
    {
        $p = Precommande::where('reference', $reference)->first();
        if (! $p || ! $p->jetonValide($request->header('X-Jeton'))) {
            return $this->erreur('Commande introuvable.', 404);
        }
        if (! in_array($p->statut, [Statut::EnAttente, Statut::AVerifier], true)) {
            return $this->erreur('Cette commande est déjà traitée.', 409);
        }

        $v = Validator::make($request->all(), [
            'capture' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:10240'],
        ], ['capture.*' => 'Envoie une image de ta confirmation Wave (jpg ou png, 10 Mo maximum).']);
        if ($v->fails()) {
            return $this->erreur($v->errors()->first(), 422);
        }

        $ancienne = $p->capture;
        $chemin = $request->file('capture')->store('captures', 'local');
        $p->update(['capture' => $chemin, 'capture_le' => now(), 'statut' => Statut::AVerifier]);
        if ($ancienne && $ancienne !== $chemin) {
            Storage::disk('local')->delete($ancienne);
        }

        Courrier::envoyerEquipe(new CaptureRecue($p), $p->reference);

        return response()->json(['reference' => $p->reference, 'statut' => $p->statut->value]);
    }

    /** @return array{0: array, 1: array<string,int>, 2: ?JsonResponse} */
    private function valider(Request $request): array
    {
        $v = Validator::make($request->all(), [
            'articles' => ['required', 'array', 'min:1', 'max:'.self::LIGNES_MAX],
            'articles.*.piece' => ['required', 'string'],
            'articles.*.taille' => ['required', Rule::in(Produit::TAILLES)],
            'articles.*.quantite' => ['required', 'integer', 'min:1', 'max:'.self::QUANTITE_MAX],
            'nom' => ['required', 'string', 'min:2', 'max:80'],
            'telephone' => ['required', 'string', 'max:30', 'regex:/^\+?[\d\s().-]{8,}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'commune' => ['required', Rule::in(Precommande::COMMUNES)],
            'quartier' => ['required', 'string', 'min:2', 'max:120'],
            'note_client' => ['nullable', 'string', 'max:500'],
        ], [
            'articles.required' => 'Ton panier est vide.',
            'articles.min' => 'Ton panier est vide.',
            'articles.max' => "Trop d'articles dans le panier.",
            'articles.*.taille.in' => 'Taille invalide.',
            'articles.*.quantite.*' => 'La quantité doit être entre 1 et '.self::QUANTITE_MAX.'.',
            'nom.*' => 'Indique ton prénom et ton nom.',
            'telephone.*' => 'Indique un numéro WhatsApp valide.',
            'email.*' => 'Adresse e-mail invalide.',
            'commune.*' => 'Choisis ta commune à Abidjan.',
            'quartier.*' => 'Indique ton quartier et un repère pour le livreur.',
            'note_client.*' => 'La note est trop longue.',
        ]);
        if ($v->fails()) {
            return [[], [], $this->erreur($v->errors()->first())];
        }
        $d = $v->validated();
        $propre = fn (?string $s) => $s === null ? null : trim(preg_replace('/\s+/', ' ', $s));

        $client = [
            'nom' => $propre($d['nom']),
            'telephone' => $propre($d['telephone']),
            'email' => filled($d['email'] ?? null) ? mb_strtolower(trim($d['email'])) : null,
            'commune' => $d['commune'],
            'quartier' => $propre($d['quartier']),
            'note_client' => filled($d['note_client'] ?? null) ? trim($d['note_client']) : null,
            'livraison' => 'yango',
        ];

        // regroupe les lignes identiques (même pièce, même taille)
        $demande = [];
        foreach ($d['articles'] as $a) {
            $k = $a['piece'].'|'.$a['taille'];
            $demande[$k] = ($demande[$k] ?? 0) + (int) $a['quantite'];
        }

        return [$client, $demande, null];
    }

    /** Vérifie et décrémente le stock (verrouillé : deux clients ne prennent jamais la même dernière pièce), crée les lignes. */
    private function reserver(Precommande $p, array $demande): void
    {
        $total = 0;
        foreach ($demande as $k => $quantite) {
            [$slug, $taille] = explode('|', $k);
            $produit = Produit::where('slug', $slug)->where('actif', true)->first()
                ?? throw new RuntimeException('Une pièce de ton panier n\'est plus disponible.');
            $stock = Stock::where('produit_id', $produit->id)->where('taille', $taille)->lockForUpdate()->first();
            if (! $stock || $stock->quantite < $quantite) {
                $reste = $stock?->quantite ?? 0;
                throw new RuntimeException($reste
                    ? "Plus que {$reste} en {$taille} pour {$produit->libelle}."
                    : "{$produit->libelle} est épuisé en {$taille}.");
            }
            $stock->decrement('quantite', $quantite);
            $p->lignes()->create([
                'produit_id' => $produit->id, 'libelle' => $produit->libelle, 'taille' => $taille,
                'quantite' => $quantite, 'prix_unitaire' => $produit->prix,
            ]);
            $total += $quantite * $produit->prix;
        }
        $p->update(['total' => $total]);
    }

    private function resume(Precommande $p): array
    {
        $p->loadMissing('lignes.produit');

        return [
            'reference' => $p->reference,
            'total' => $p->total,
            'lignes' => $p->lignes->map(fn ($l) => [
                'libelle' => trim(($l->produit?->type ? $l->produit->type.' ' : '').($l->produit?->nom ?? $l->libelle)).($l->produit ? ' '.$l->produit->couleur : ''),
                'taille' => $l->taille,
                'quantite' => $l->quantite,
            ]),
            'adresse' => $p->adresse,
            'telephone' => $p->telephone,
        ];
    }

    private function erreur(string $message, int $code = 400): JsonResponse
    {
        return response()->json(['erreur' => $message], $code);
    }
}
