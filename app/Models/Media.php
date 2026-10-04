<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['zone', 'ordre', 'fichier', 'poster', 'legende'])]
class Media extends Model
{
    protected $table = 'medias';

    /** Fichier livré avec le site (public/…) ou envoyé depuis le back office (storage, dossier site/). */
    public static function url(?string $chemin): ?string
    {
        if (! $chemin || Str::startsWith($chemin, ['http://', 'https://'])) {
            return $chemin;
        }

        return Str::startsWith($chemin, ['site/', 'produits/']) ? Storage::disk('public')->url($chemin) : asset($chemin);
    }

    public function getUrlAttribute(): ?string
    {
        return static::url($this->fichier);
    }

    public function getPosterUrlAttribute(): ?string
    {
        return static::url($this->poster);
    }

    public function getEstVideoAttribute(): bool
    {
        return (bool) preg_match('/\.(mp4|webm|mov)$/i', $this->fichier);
    }

    /** Tous les médias du site, regroupés par zone et triés (une petite requête par page). */
    public static function duSite(): Collection
    {
        return static::orderBy('zone')->orderBy('ordre')->orderBy('id')->get()->groupBy('zone');
    }
}
