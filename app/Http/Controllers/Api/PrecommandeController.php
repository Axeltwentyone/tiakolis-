<?php

namespace App\Http\Controllers\Api;

use App\Enums\Statut;
use App\Http\Controllers\Controller;
use App\Mail\CaptureRecue;
use App\Models\Precommande;
use App\Support\Courrier;
use App\Support\Panier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
    private const LIMITE = 5;          // précommandes créées par IP…

    private const FENETRE = 10 * 60;   // …sur 10 minutes

    /** Étape 2 → 3 : tout est-il bon (coordonnées, stock) ? Renvoie le récapitulatif et le montant à payer. */
    public function verifier(Request $request): JsonResponse
    {
        [$client, $demande, $erreur] = Panier::valider($request->all());
        if ($erreur) {
            return $this->erreur($erreur);
        }
        try {
            $chiffre = Panier::chiffrer($demande);
        } catch (RuntimeException $e) {
            return $this->erreur($e->getMessage(), 409);
        }

        return response()->json($chiffre + [
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

        [$client, $demande, $erreur] = Panier::valider($donnees);
        if ($erreur) {
            return $this->erreur($erreur);
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
                Panier::reserver($p, $demande);

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

    private function erreur(string $message, int $code = 400): JsonResponse
    {
        return response()->json(['erreur' => $message], $code);
    }
}
