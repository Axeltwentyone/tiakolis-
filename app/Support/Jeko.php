<?php

namespace App\Support;

use App\Enums\Statut;
use App\Mail\CaptureRecue;
use App\Models\Paiement;
use App\Models\Precommande;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Jeko — paiement en ligne (Orange Money, Wave, MTN, Moov) : https://developer.jeko.africa
 *   1. creerDemande()  : POST /partner_api/payment_requests → lien de paiement pay.jeko.africa
 *   2. le client paie chez Jeko, puis revient sur /paiement/retour/{reference}
 *   3. webhook signé (TRANSACTION_COMPLETED) ou vérification du statut → finaliser() crée la précommande, payée
 */
class Jeko
{
    public const MOYENS = ['orange' => 'Orange Money', 'wave' => 'Wave', 'mtn' => 'MTN MoMo', 'moov' => 'Moov Money', 'djamo' => 'Djamo'];

    public static function actif(): bool
    {
        $c = config('services.jeko');

        return filled($c['api_key'] ?? null) && filled($c['api_key_id'] ?? null) && filled($c['store_id'] ?? null);
    }

    /** Moyens proposés sur le site (JEKO_MOYENS), dans l'ordre. */
    public static function moyens(): array
    {
        return self::actif() ? array_values(array_intersect((array) config('services.jeko.moyens'), array_keys(self::MOYENS))) : [];
    }

    private static function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.jeko.url'), '/'))
            ->withHeaders(['X-API-KEY' => config('services.jeko.api_key'), 'X-API-KEY-ID' => config('services.jeko.api_key_id')])
            ->acceptJson()->asJson()->timeout(20);
    }

    /** Moyens en « paiement direct » : le lien renvoyé ouvre l'appli de l'opérateur (pay.wave.com, page Orange Money). */
    public const DIRECTS = ['wave', 'orange'];

    /** Numéro ivoirien au format attendu par Jeko : +225 puis 01, 05 ou 07 et 8 chiffres. Null si autre format. */
    public static function telephone(?string $numero): ?string
    {
        $c = preg_replace('/\D/', '', (string) $numero);
        if (strlen($c) === 13 && str_starts_with($c, '225')) {
            $c = substr($c, 3);
        }

        return preg_match('/^0[157]\d{8}$/', $c) ? '+225'.$c : null;
    }

    /** Demande de paiement « redirect » : renvoie la réponse Jeko (id, status, redirectUrl…). */
    public static function creerDemande(Paiement $p, string $urlSucces, string $urlErreur): array
    {
        $data = ['paymentMethod' => $p->moyen, 'successUrl' => $urlSucces, 'errorUrl' => $urlErreur];
        // Wave / Orange Money : directement chez l'opérateur (sur mobile, le lien Wave ouvre l'application Wave)
        $tel = self::telephone($p->donnees['telephone'] ?? null);
        if (in_array($p->moyen, self::DIRECTS, true) && $tel) {
            $data += ['forceProviderDirect' => true, 'payerPhone' => $tel];
        }

        return self::http()->post('/partner_api/payment_requests', [
            'storeId' => config('services.jeko.store_id'),
            'amountCents' => $p->montant * 100,     // Jeko compte en centimes : 12 500 FCFA = 1 250 000
            'currency' => 'XOF',
            'reference' => $p->reference,
            'paymentDetails' => ['type' => 'redirect', 'data' => $data],
        ])->throw()->json();
    }

    /** Statut d'une demande de paiement chez Jeko : « pending », « success », « error »… */
    public static function statut(string $id): array
    {
        return self::http()->get('/partner_api/payment_requests/'.rawurlencode($id))->throw()->json();
    }

    /** Signature du webhook : HMAC-SHA256 (hexadécimal) du corps brut avec le secret du webhook. */
    public static function signatureValide(string $corps, ?string $signature): bool
    {
        $secret = (string) config('services.jeko.webhook_secret');

        return $secret !== '' && is_string($signature) && hash_equals(hash_hmac('sha256', $corps, $secret), strtolower(trim($signature)));
    }

    /** Référence TEF-XXXXX libre à la fois pour les précommandes et les paiements en cours. */
    public static function nouvelleReference(): string
    {
        do {
            $ref = Precommande::nouvelleReference();
        } while (Paiement::where('reference', $ref)->exists());

        return $ref;
    }

    /**
     * Paiement confirmé par Jeko → la précommande est créée, payée. Sans effet si c'est déjà fait (webhook + retour client).
     * Si le stock est parti entre-temps, la précommande est quand même créée (le client a payé) mais reste « à vérifier »
     * avec une note : l'équipe propose une autre taille ou rembourse.
     */
    public static function finaliser(Paiement $paiement, ?string $transaction = null): ?Precommande
    {
        $manques = [];
        $precommande = DB::transaction(function () use ($paiement, $transaction, &$manques) {
            $p = Paiement::lockForUpdate()->find($paiement->id);
            if ($p->statut === 'paye') {
                return null; // déjà traité
            }
            [$client, $demande, $erreur] = Panier::valider($p->donnees);
            if ($erreur) {
                Log::error("Jeko {$p->reference} : panier invalide après paiement", ['erreur' => $erreur]);
                $p->update(['statut' => 'echec']);

                return null;
            }
            $commande = Precommande::create($client + [
                'reference' => $p->reference,
                'jeton' => hash('sha256', Str::random(40)),
                'total' => 0,
                'statut' => Statut::AVerifier,
                'paiement' => 'jeko · '.$p->moyen,
                'note' => 'Payé en ligne avec Jeko ('.(self::MOYENS[$p->moyen] ?? $p->moyen).')'.($transaction ? ", transaction {$transaction}" : '').'.',
            ]);
            $manques = Panier::reserver($commande, $demande, strict: false);
            if ($manques) {
                $commande->update(['note' => $commande->note."\n⚠️ Stock insuffisant au moment du paiement : ".implode(' ', $manques).' Proposer une autre taille ou rembourser.']);
            }
            $p->update(['statut' => 'paye', 'precommande_id' => $commande->id]);

            return $commande;
        });

        if (! $precommande) {
            return $paiement->fresh()->precommande;
        }
        if ($manques) {
            Courrier::envoyerEquipe(new CaptureRecue($precommande), $precommande->reference); // « à vérifier » : stock manquant
        } else {
            $precommande->update(['statut' => Statut::Payee]); // e-mails « C'est validé ! » au client et « Paiement validé » à l'équipe
        }

        return $precommande;
    }
}
