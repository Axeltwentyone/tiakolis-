<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Statut;
use App\Http\Controllers\Controller;
use App\Mail\RelancePaiement;
use App\Models\Precommande;
use App\Support\Courrier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrecommandeController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.precommandes.index', [
            'precommandes' => $this->filtre($request)->with('lignes.produit')->latest()->paginate(20)->withQueryString(),
            'compteurs' => Precommande::selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut'),
            'statut' => Statut::tryFrom((string) $request->query('statut')),
            'q' => (string) $request->query('q'),
        ]);
    }

    public function show(Precommande $precommande): View
    {
        return view('admin.precommandes.show', ['p' => $precommande->load('lignes.produit')]);
    }

    public function statut(Request $request, Precommande $precommande): RedirectResponse
    {
        $data = $request->validate(['statut' => ['required', Rule::enum(Statut::class)]]);
        $precommande->update($data); // « Annulée » remet les pièces en stock (événement du modèle)

        $message = "{$precommande->reference} : ".$precommande->statut->label().'.';
        if ($precommande->wasChanged('statut') && $precommande->statut === Statut::Payee) {
            $message .= $precommande->email ? ' E-mail « C\'est validé ! » envoyé au client.' : ' Pas d\'e-mail client : préviens-le sur WhatsApp.';
        }

        return back()->with('ok', $message);
    }

    public function update(Request $request, Precommande $precommande): RedirectResponse
    {
        $precommande->update($request->validate([
            'nom' => ['required', 'string', 'min:2', 'max:80'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'commune' => ['required', Rule::in(Precommande::COMMUNES)],
            'quartier' => ['required', 'string', 'min:2', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]));

        return back()->with('ok', 'Enregistré.');
    }

    /** Relancer un client en attente de paiement : e-mail (s'il en a donné un) + date de relance. */
    public function relancer(Precommande $precommande): RedirectResponse
    {
        if (! $precommande->modifiable()) {
            return back()->with('ok', "{$precommande->reference} n'est plus en attente de paiement.");
        }
        if (! $precommande->email) {
            return back()->with('ok', "{$precommande->nom} n'a pas donné d'e-mail : relance-le avec le bouton WhatsApp.");
        }
        $this->envoyerRelance($precommande);

        return back()->with('ok', "Relance envoyée à {$precommande->nom} ({$precommande->email}).");
    }

    /** Relancer d'un coup tous les clients en attente de paiement qui ont un e-mail (sauf relancés il y a moins de 24 h). */
    public function relancerTous(): RedirectResponse
    {
        $cibles = Precommande::attentePaiement()->whereNotNull('email')
            ->where(fn ($q) => $q->whereNull('relance_le')->orWhere('relance_le', '<', now()->subDay()))->get();
        $cibles->each(fn ($p) => $this->envoyerRelance($p));

        $sansMail = Precommande::attentePaiement()->whereNull('email')->count();
        $message = $cibles->count() ? "{$cibles->count()} relance(s) envoyée(s) par e-mail." : 'Personne à relancer par e-mail pour le moment (déjà relancés il y a moins de 24 h).';
        if ($sansMail) {
            $message .= " {$sansMail} client(s) sans e-mail : utilise le bouton WhatsApp.";
        }

        return back()->with('ok', $message);
    }

    private function envoyerRelance(Precommande $p): void
    {
        Courrier::envoyer($p->email, new RelancePaiement($p), $p->reference);
        $p->update(['relance_le' => now(), 'relances' => $p->relances + 1]);
    }

    /** Capture du paiement Wave : fichier privé, visible seulement une fois connecté. */
    public function capture(Precommande $precommande): StreamedResponse
    {
        abort_unless($precommande->capture && Storage::disk('local')->exists($precommande->capture), 404);

        return Storage::disk('local')->response($precommande->capture, $precommande->reference.'-capture.'.pathinfo($precommande->capture, PATHINFO_EXTENSION));
    }

    public function destroy(Precommande $precommande): RedirectResponse
    {
        $ref = $precommande->reference;
        $capture = $precommande->capture;
        $precommande->delete(); // rend aussi les pièces au stock si elle n'était pas annulée
        if ($capture) {
            Storage::disk('local')->delete($capture);
        }

        return redirect()->route('admin.precommandes.index')->with('ok', "{$ref} supprimée, pièces remises en stock.");
    }

    /** Une ligne par article, avec le filtre et la recherche en cours ; s'ouvre directement dans Excel. */
    public function export(Request $request): StreamedResponse
    {
        $precommandes = $this->filtre($request)->with('lignes')->latest()->get();
        $cell = function ($v) {
            $s = (string) ($v ?? '');
            if (preg_match('/^[=+\-@]/', $s)) {
                $s = "'".$s; // évite l'injection de formules dans Excel
            }

            return '"'.str_replace('"', '""', $s).'"';
        };

        return response()->streamDownload(function () use ($precommandes, $cell) {
            echo "\u{FEFF}".implode(';', ['reference', 'date', 'piece', 'taille', 'quantite', 'prix_unitaire', 'total_commande', 'nom', 'telephone', 'email', 'commune', 'quartier', 'note_client', 'statut', 'capture_le', 'note_interne'])."\r\n";
            foreach ($precommandes as $p) {
                foreach ($p->lignes as $l) {
                    echo implode(';', array_map($cell, [$p->reference, $p->created_at->format('Y-m-d H:i'), $l->libelle, $l->taille, $l->quantite, $l->prix_unitaire, $p->total, $p->nom, $p->telephone, $p->email, $p->commune, $p->quartier, $p->note_client, $p->statut->label(), $p->capture_le?->format('Y-m-d H:i'), $p->note]))."\r\n";
                }
            }
        }, 'precommandes-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    /** ?statut=… et ?q=… (référence TEF-XXXXX, nom, téléphone, e-mail, commune ou quartier) */
    private function filtre(Request $request): Builder
    {
        $query = Precommande::query();
        if ($statut = Statut::tryFrom((string) $request->query('statut'))) {
            $query->where('statut', $statut);
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($w) use ($q) {
                foreach (['reference', 'nom', 'telephone', 'email', 'commune', 'quartier'] as $col) {
                    $w->orWhere($col, 'like', '%'.addcslashes($q, '%_\\').'%');
                }
            });
        }

        return $query;
    }
}
