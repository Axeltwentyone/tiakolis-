<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Statut;
use App\Http\Controllers\Controller;
use App\Models\Ligne;
use App\Models\Precommande;
use App\Models\Produit;
use Illuminate\View\View;

class TableauController extends Controller
{
    public function __invoke(): View
    {
        $actives = Precommande::where('statut', '!=', Statut::Annulee);
        $reservees = Ligne::whereHas('precommande', fn ($q) => $q->where('statut', '!=', Statut::Annulee))->sum('quantite');
        $pieces = Produit::orderBy('ordre')->with('stocks')->get();
        $restant = $pieces->where('actif', true)->sum(fn ($p) => $p->stocks->sum('quantite'));

        return view('admin.tableau', [
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
