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

    public function precommande(): BelongsTo
    {
        return $this->belongsTo(Precommande::class);
    }
}
