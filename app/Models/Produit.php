<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['slug', 'type', 'nom', 'couleur', 'prix', 'image_face', 'image_dos', 'actif', 'ordre'])]
class Produit extends Model
{
    public const TAILLES = ['S', 'M', 'L', 'XL'];

    protected function casts(): array
    {
        return ['actif' => 'boolean', 'prix' => 'integer'];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class)->orderBy('ordre');
    }

    /** Chemin du fichier image sur le disque (pour l'intégrer dans les e-mails). */
    public static function fichierImage(?string $chemin): ?string
    {
        if (! $chemin || Str::startsWith($chemin, ['http://', 'https://'])) {
            return null;
        }
        $fichier = Str::startsWith($chemin, 'assets/') ? public_path($chemin) : Storage::disk('public')->path($chemin);

        return is_file($fichier) ? $fichier : null;
    }

    public function getLibelleAttribute(): string
    {
        return "{$this->nom} · {$this->couleur}";
    }

    /**
     * Images d'origine : fichiers livrés avec le site (public/assets/…).
     * Images envoyées depuis le back office : disque public (storage/app/public/produits/…).
     */
    public static function urlImage(?string $chemin): ?string
    {
        if (! $chemin || Str::startsWith($chemin, ['http://', 'https://'])) {
            return $chemin;
        }
        if (Str::startsWith($chemin, 'assets/')) {
            return asset($chemin);
        }

        return Storage::disk('public')->url($chemin);
    }
}
