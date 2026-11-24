<?php

namespace App\Support;

use App\Models\Precommande;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

/** Panier + coordonnées du client : validation, montant, réservation du stock. Partagé par la capture et Jeko. */
class Panier
{
    public const QUANTITE_MAX = 5;   // par ligne

    public const LIGNES_MAX = 10;

    /** @return array{0: array, 1: array<string,int>, 2: ?string} [coordonnées, demande « slug|taille » => quantité, erreur] */
    public static function valider(array $entree): array
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
            return [[], [], $v->errors()->first()];
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

    /**
     * Montant et récapitulatif, avec vérification du stock (sans rien réserver).
     *
     * @return array{total: int, lignes: array}
     *
     * @throws RuntimeException pièce indisponible ou stock insuffisant
     */
    public static function chiffrer(array $demande): array
    {
        $total = 0;
        $lignes = [];
        foreach ($demande as $k => $quantite) {
            [$slug, $taille] = explode('|', $k);
            $produit = Produit::where('slug', $slug)->where('actif', true)->with('stocks')->first()
                ?? throw new RuntimeException('Une pièce de ton panier n\'est plus disponible.');
            $reste = (int) $produit->stocks->firstWhere('taille', $taille)?->quantite;
            if ($reste < $quantite) {
                throw new RuntimeException(self::messageStock($produit, $taille, $reste));
            }
            $lignes[] = ['libelle' => "{$produit->type} {$produit->nom} {$produit->couleur}", 'taille' => $taille, 'quantite' => $quantite];
            $total += $quantite * $produit->prix;
        }

        return ['total' => $total, 'lignes' => $lignes];
    }

    /**
     * Décrémente le stock (verrouillé : deux clients ne prennent jamais la même dernière pièce) et crée les lignes.
     * Strict : stock insuffisant = exception. Non strict (paiement déjà encaissé) : la commande est créée quand même,
     * le stock manquant n'est pas décrémenté et les problèmes sont renvoyés pour que l'équipe s'en occupe.
     *
     * @return string[] problèmes de stock (vide si tout est réservé)
     */
    public static function reserver(Precommande $p, array $demande, bool $strict = true): array
    {
        $total = 0;
        $manques = [];
        foreach ($demande as $k => $quantite) {
            [$slug, $taille] = explode('|', $k);
            $produit = Produit::where('slug', $slug)->first();
            if (! $produit || (! $produit->actif && $strict)) {
                throw new RuntimeException('Une pièce de ton panier n\'est plus disponible.');
            }
            $stock = Stock::where('produit_id', $produit->id)->where('taille', $taille)->lockForUpdate()->first();
            if (! $stock || $stock->quantite < $quantite) {
                $message = self::messageStock($produit, $taille, $stock?->quantite ?? 0);
                if ($strict) {
                    throw new RuntimeException($message);
                }
                $manques[] = $message;
            } else {
                $stock->decrement('quantite', $quantite);
            }
            $p->lignes()->create([
                'produit_id' => $produit->id, 'libelle' => $produit->libelle, 'taille' => $taille,
                'quantite' => $quantite, 'prix_unitaire' => $produit->prix,
            ]);
            $total += $quantite * $produit->prix;
        }
        $p->update(['total' => $total]);

        return $manques;
    }

    public static function messageStock(Produit $produit, string $taille, int $reste): string
    {
        return $reste ? "Plus que {$reste} en {$taille} pour {$produit->libelle}." : "{$produit->libelle} est épuisé en {$taille}.";
    }
}
