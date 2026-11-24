<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Support\Jeko;
use App\Support\Panier;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/** Paiement en ligne Jeko : départ vers la page de paiement, webhook de confirmation, page de retour du client. */
class JekoController extends Controller
{
    /** POST /api/paiements/jeko : panier + coordonnées + moyen → lien de paiement Jeko. Rien n'est réservé à ce stade. */
    public function creer(Request $request): JsonResponse
    {
        if (! Jeko::actif()) {
            return response()->json(['erreur' => 'Le paiement en ligne n\'est pas disponible.'], 404);
        }
        if ($request->filled('website')) { // pot de miel
            return response()->json(['erreur' => 'Requête invalide.'], 400);
        }
        $cle = 'jeko:'.$request->ip();
        if (RateLimiter::tooManyAttempts($cle, 20)) { // un peu de marge : le lien Wave est préparé dès l'écran Paiement
            return response()->json(['erreur' => 'Trop de tentatives. Réessaie dans quelques minutes.'], 429);
        }
        RateLimiter::hit($cle, 10 * 60);

        $moyen = (string) $request->input('moyen');
        if (! in_array($moyen, Jeko::moyens(), true)) {
            return response()->json(['erreur' => 'Choisis un moyen de paiement.'], 400);
        }
        [, $demande, $erreur] = Panier::valider($request->all());
        if ($erreur) {
            return response()->json(['erreur' => $erreur], 400);
        }
        try {
            $montant = Panier::chiffrer($demande)['total'];
        } catch (RuntimeException $e) {
            return response()->json(['erreur' => $e->getMessage()], 409);
        }

        $paiement = Paiement::create([
            'reference' => Jeko::nouvelleReference(),
            'moyen' => $moyen,
            'montant' => $montant,
            'donnees' => $request->except(['moyen', 'website']),
        ]);
        $base = rtrim((string) (config('services.jeko.url_retour') ?: url('/')), '/');
        $retour = $base.'/paiement/retour/'.$paiement->reference; // Jeko exige une adresse publique en https
        try {
            $reponse = Jeko::creerDemande($paiement, $retour, $retour.'?echec=1');
        } catch (Throwable $e) {
            Log::error("Jeko {$paiement->reference} : création du paiement impossible", [
                'erreur' => $e->getMessage(),
                'reponse' => $e instanceof RequestException ? $e->response->body() : null, // ex. champ refusé par Jeko
            ]);
            $paiement->update(['statut' => 'echec']);

            return response()->json(['erreur' => 'Le paiement en ligne ne répond pas pour le moment. Réessaie, ou paie avec le lien et envoie ta capture.'], 502);
        }
        $paiement->update(['externe_id' => $reponse['id'] ?? null]);

        if (blank($reponse['redirectUrl'] ?? null)) {
            Log::error("Jeko {$paiement->reference} : pas de lien de paiement", ['reponse' => $reponse]);

            return response()->json(['erreur' => 'Le paiement en ligne ne répond pas pour le moment. Réessaie dans un instant.'], 502);
        }

        return response()->json(['reference' => $paiement->reference, 'redirectUrl' => $reponse['redirectUrl']]);
    }

    /** POST /api/jeko/webhook : Jeko confirme un paiement (signature HMAC-SHA256 du corps brut). */
    public function webhook(Request $request): JsonResponse
    {
        $corps = $request->getContent();
        if (! Jeko::signatureValide($corps, $request->header('Jeko-Signature'))) {
            Log::warning('Jeko webhook : signature invalide');

            return response()->json(['erreur' => 'Signature invalide.'], 401);
        }
        $t = json_decode($corps, true) ?: [];
        if (($t['status'] ?? null) !== 'success' || ($t['transactionType'] ?? 'payment') !== 'payment') {
            return response()->json(['ok' => true]); // autre événement, ou paiement pas encore réussi
        }
        $details = $t['transactionDetails'] ?? [];
        $paiement = Paiement::when(filled($details['id'] ?? null), fn ($q) => $q->where('externe_id', $details['id']))
            ->when(blank($details['id'] ?? null), fn ($q) => $q->where('reference', $details['reference'] ?? '-'))
            ->first() ?? Paiement::where('reference', $details['reference'] ?? '-')->first();
        if (! $paiement) {
            Log::warning('Jeko webhook : paiement inconnu', ['details' => $details]);

            return response()->json(['ok' => true]);
        }
        Jeko::finaliser($paiement, $t['id'] ?? null);

        return response()->json(['ok' => true]);
    }

    /** GET /paiement/retour/{reference} : le client revient de Jeko. On vérifie le statut chez Jeko (sans attendre le webhook). */
    public function retour(Request $request, string $reference): View
    {
        $paiement = Paiement::where('reference', $reference)->firstOrFail();

        if ($paiement->statut === 'en_cours' && $paiement->externe_id) {
            try {
                $demande = Jeko::statut($paiement->externe_id);
                $statut = $demande['status'] ?? 'pending';
                if ($statut === 'success') {
                    Jeko::finaliser($paiement, $demande['transaction']['id'] ?? null);
                } elseif (in_array($statut, ['error', 'failed', 'expired', 'cancelled', 'canceled'], true)) {
                    $paiement->update(['statut' => 'echec']);
                }
            } catch (Throwable $e) {
                Log::warning("Jeko {$reference} : statut indisponible", ['erreur' => $e->getMessage()]);
            }
            $paiement->refresh();
        }

        $etat = match (true) {
            $paiement->statut === 'paye' => 'paye',
            $paiement->statut === 'echec', $request->boolean('echec') && $paiement->statut !== 'paye' => 'echec',
            default => 'attente',
        };

        return view('paiement-retour', [
            'p' => $paiement->load('precommande'),
            'etat' => $etat,
            'moyen' => Jeko::MOYENS[$paiement->moyen] ?? $paiement->moyen,
            'whatsapp' => preg_replace('/\D/', '', (string) config('services.precommandes.whatsapp_numero')),
        ]);
    }
}
