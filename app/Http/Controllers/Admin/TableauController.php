<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Statut;
use App\Http\Controllers\Controller;
use App\Models\Ligne;
use App\Models\Precommande;
use App\Models\Paiement;
use App\Models\Produit;
use App\Support\Jeko;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TableauController extends Controller
{
    public function __invoke(): View
    {
        $actives = Precommande::where('statut', '!=', Statut::Annulee);
        $reservees = Ligne::whereHas('precommande', fn ($q) => $q->where('statut', '!=', Statut::Annulee))->sum('quantite');
        $pieces = Produit::orderBy('ordre')->with('stocks')->get();
        $restant = $pieces->where('actif', true)->sum(fn ($p) => $p->stocks->sum('quantite'));

        $payees = Precommande::whereIn('statut', [Statut::Payee, Statut::Livree, Statut::AVerifier]);

        // paniers non payés : arrivés au paiement en ligne sans le terminer (30 min passées), un par client,
        // sauf si le client a commandé depuis ou si l'équipe a cliqué « Ignorer »
        $tentatives = Paiement::whereIn('statut', ['en_cours', 'echec'])->where('created_at', '<', now()->subMinutes(30))
            ->where('created_at', '>', now()->subDays(14))->latest()->get();
        $abandons = $tentatives->unique(fn ($t) => mb_strtolower((string) ($t->donnees['email'] ?? $t->id)))
            ->reject(fn ($t) => Precommande::where('email', mb_strtolower((string) ($t->donnees['email'] ?? '-')))->where('created_at', '>=', $t->created_at)->exists())
            ->values();

        return view('admin.tableau', [
            // paiements : en ligne (Jeko) et par capture, répartis par moyen
            'enLigne' => Precommande::where('paiement', 'like', 'jeko%')->whereIn('statut', [Statut::Payee, Statut::Livree])->sum('total'),
            'nbEnLigne' => Precommande::where('paiement', 'like', 'jeko%')->whereIn('statut', [Statut::Payee, Statut::Livree])->count(),
            'parMoyen' => (clone $payees)->selectRaw('paiement, count(*) as n, sum(total) as t')->groupBy('paiement')->get()
                ->map(fn ($r) => ['moyen' => $r->paiement ? (Jeko::MOYENS[Str::after($r->paiement, 'jeko · ')] ?? $r->paiement) : 'Capture (lien)', 'en_ligne' => (bool) $r->paiement, 'n' => (int) $r->n, 't' => (int) $r->t])
                ->sortByDesc('t')->values(),
            'abandons' => $abandons->take(8),
            'nbAbandons' => $abandons->count(),
            'nombre' => (clone $actives)->count(),
            'aVerifier' => Precommande::where('statut', Statut::AVerifier)->count(),
            'enAttente' => Precommande::where('statut', Statut::EnAttente)->count(),
            'prevu' => (clone $actives)->sum('total'),
            'encaisse' => Precommande::whereIn('statut', [Statut::Payee, Statut::Livree])->sum('total'),
            'reservees' => (int) $reservees,
            'restant' => (int) $restant,
            'pieces' => $pieces,
            // en attente de paiement : les plus anciennes d'abord (ce sont elles qu'il faut relancer)
            'attente' => Precommande::attentePaiement()->with('lignes')->oldest()->limit(8)->get(),
            'nbAttente' => Precommande::attentePaiement()->count(),
            'aRelancerMail' => Precommande::attentePaiement()->whereNotNull('email')
                ->where(fn ($q) => $q->whereNull('relance_le')->orWhere('relance_le', '<', now()->subDay()))->count(),
            'dernieres' => Precommande::with('lignes')->latest()->limit(6)->get(),
        ]);
    }
}
