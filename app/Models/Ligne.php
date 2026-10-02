<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['precommande_id', 'produit_id', 'libelle', 'taille', 'quantite', 'prix_unitaire'])]
class Ligne extends Model
{
    protected function casts(): array
    {
        return ['quantite' => 'integer', 'prix_unitaire' => 'integer'];
    }

    public function precommande(): BelongsTo
    {
        return $this->belongsTo(Precommande::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function getSousTotalAttribute(): int
    {
        return $this->quantite * $this->prix_unitaire;
    }
}
