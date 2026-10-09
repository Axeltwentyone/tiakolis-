<?php

namespace App\Http\Controllers\Api;

use App\Enums\Statut;
use App\Http\Controllers\Controller;
use App\Mail\CaptureRecue;
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
 * Parcours du tiroir panier — aucune précommande n'existe tant que le client n'a pas payé :
 *   1. POST /api/precommandes/verifier   → vérifie coordonnées et stock, RIEN n'est enregistré (montant à payer)
 *   2. le client paie (lien Wave ou Orange Money), puis envoie la capture de son paiement
 *   3. POST /api/precommandes            → capture obligatoire : crée la précommande (« Capture à vérifier »),
 *                                          réserve le stock, l'équipe reçoit l'e-mail « Nouveau paiement »
 */
class PrecommandeController extends Controller
{
    private const QUANTITE_MAX = 5;   // par ligne

    private const LIGNES_MAX = 10;

    private const LIMITE = 5;          // précommandes créées par IP…

    private const FENETRE = 10 * 60;   // …sur 10 minutes

    /** Étape 2 → 3 : tout est-il bon (coordonnées, stock) ? Renvoie le récapitulatif et le montant à payer. */
    public function verifier(Request $request): JsonResponse
    {
        [$client, $demande, $erreur] = $this->valider($request->all());
        if ($erreur) {
            return $erreur;
        }

        $total = 0;
        $lignes = [];
        foreach ($demande as $k => $quantite) {
            [$slug, $taille] = explode('|', $k);
            $produit = Produit::where('slug', $slug)->where('actif', true)->with('stocks')->first();
            if (! $produit) {
                return $this->erreur('Une pièce de ton panier n\'est plus disponible.', 409);
            }
            $reste = (int) $produit->stocks->firstWhere('taille', $taille)?->quantite;
            if ($reste < $quantite) {
                return $this->erreur($this->messageStock($produit, $taille, $reste), 409);
            }
            $lignes[] = ['libelle' => "{$produit->type} {$produit->nom} {$produit->couleur}", 'taille' => $taille, 'quantite' => $quantite];
            $total += $quantite * $produit->prix;
        }

        return response()->json([
            'total' => $total,
            'lignes' => $lignes,
            'adresse' => "{$client['quartier']}, {$client['commune']} (Abidjan)",
            'telephone' => $client['telephone'],
        ]);
    }

    /** Étape 3 : le client a payé et envoie sa capture → la précommande est créée. */
    public function store(Request $request): JsonResponse
    {
        $donnees = json_decode((string) $request->input('donnees'), true);
        if (! is_array($donnees)) {
            return $this->erreur('Requête invalide.');
        }
        // pot de miel rempli = robot : on répond OK sans rien enregistrer
        if (filled($donnees['website'] ?? null)) {
            return response()->json(['reference' => 'TEF-AAAAA', 'total' => 0], 201);
        }

        $cle = 'precommande:'.$request->ip();
        if (RateLimiter::tooManyAttempts($cle, self::LIMITE)) {
            return $this->erreur('Trop de précommandes. Réessaie dans quelques minutes.', 429);
        }

        $v = Validator::make($request->all(), [
            'capture' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:10240'],
        ], [
            'capture.required' => 'Envoie la capture d\'écran de ton paiement : sans paiement, la précommande n\'est pas validée.',
            'capture.*' => 'Envoie une image de ta confirmation de paiement (jpg ou png, 10 Mo maximum).',
        ]);
        if ($v->fails()) {
            return $this->erreur($v->errors()->first(), 422);
        }

        [$client, $demande, $erreur] = $this->valider($donnees);
        if ($erreur) {
            return $erreur;
        }

        $capture = $request->file('capture')->store('captures', 'local');
        try {
            $p = DB::transaction(function () use ($client, $demande, $capture) {
                $p = Precommande::create($client + [
                    'reference' => Precommande::nouvelleReference(),
                    'jeton' => hash('sha256', Str::random(40)),
                    'total' => 0,
                    'statut' => Statut::AVerifier,
                    'capture' => $capture,
                    'capture_le' => now(),
                ]);
                $this->reserver($p, $demande);

                return $p;
            });
        } catch (RuntimeException $e) {
            // stock parti entre-temps alors que le client a déjà payé : on garde la trace et on l'oriente vers WhatsApp
            Storage::disk('local')->delete($capture);

            return $this->erreur($e->getMessage().' Ton paiement est bien parti : écris-nous sur WhatsApp avec ta capture, on te propose une autre taille ou on te rembourse.', 409);
        }

        RateLimiter::hit($cle, self::FENETRE);
        Courrier::envoyerEquipe(new CaptureRecue($p), $p->reference);

        return response()->json(['reference' => $p->reference, 'total' => $p->total], 201);
    }

    /** @return array{0: array, 1: array<string,int>, 2: ?JsonResponse} */
    private function valider(array $entree): array
    {
        $v = Validator::make($entree, [
            'articles' => ['required', 'array', 'min:1', 'max:'.self::LIGNES_MAX],
            'articles.*.piece' => ['required', 'string'],
            'articles.*.taille' => ['required', Rule::in(Produit::TAILLES)],
            'articles.*.quantite' => ['required', 'integer', 'min:1', 'max:'.self::QUANTITE_MAX],
            'nom' => ['required', 'string', 'min:2', 'max:80'],
            'telephone' => ['required', 'string', 'max:30', 'regex:/^\+?[\d\s().-]{8,}$/'],
            'email' => ['required', 'email', 'max:120'],
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
            'email.required' => 'Indique ton e-mail : on y envoie la confirmation de ta précommande.',
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
            'email' => mb_strtolower(trim($d['email'])),
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
                throw new RuntimeException($this->messageStock($produit, $taille, $stock?->quantite ?? 0));
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

    private function messageStock(Produit $produit, string $taille, int $reste): string
    {
        return $reste ? "Plus que {$reste} en {$taille} pour {$produit->libelle}." : "{$produit->libelle} est épuisé en {$taille}.";
    }

    private function erreur(string $message, int $code = 400): JsonResponse
    {
        return response()->json(['erreur' => $message], $code);
    }
}
