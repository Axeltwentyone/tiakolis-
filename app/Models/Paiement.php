<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Paiement en ligne en attente de confirmation (Jeko). Voir App\Support\Jeko. */
#[Fillable(['reference', 'fournisseur', 'moyen', 'montant', 'donnees', 'externe_id', 'statut', 'precommande_id'])]
class Paiement extends Model
{
    protected function casts(): array
    {
        return ['donnees' => 'array', 'montant' => 'integer'];
    }

    /** « 2 × Mélo Décalé Noir (M), 1 × … » à partir du panier enregistré. */
    public function getResumeAttribute(): string
    {
        $produits = Produit::pluck('nom', 'slug');
        $couleurs = Produit::pluck('couleur', 'slug');

        return collect($this->donnees['articles'] ?? [])->map(fn ($a) => ($a['quantite'] ?? 1).' × '.($produits[$a['piece']] ?? $a['piece']).' '.($couleurs[$a['piece']] ?? '').' ('.($a['taille'] ?? '?').')')->implode(', ');
    }

    /** Lien WhatsApp vers le client avec un message pour finaliser sa précommande. */
    public function getWhatsappRelanceAttribute(): string
    {
        $chiffres = preg_replace('/\D/', '', (string) ($this->donnees['telephone'] ?? ''));
        $numero = strlen($chiffres) === 10 ? '225'.$chiffres : $chiffres;
        $prenom = explode(' ', trim((string) ($this->donnees['nom'] ?? '')))[0];
        $texte = "Bonjour {$prenom} ! C'est Tiakolisé et fière 👋 Tu as commencé une précommande ({$this->resume}, ".fcfa($this->montant).") mais le paiement n'est pas allé au bout. Tu peux la finaliser ici : ".url('/').' C\'est une série limitée, on te garde une place ! Un souci avec le paiement ? Réponds-nous ici.';

        return 'https://wa.me/'.$numero.'?text='.rawurlencode($texte);
    }

    public function precommande(): BelongsTo
    {
        return $this->belongsTo(Precommande::class);
    }
}
