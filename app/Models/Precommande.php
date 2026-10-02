<?php

namespace App\Models;

use App\Enums\Statut;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['reference', 'jeton', 'nom', 'telephone', 'email', 'commune', 'quartier', 'note_client', 'livraison', 'total', 'statut', 'capture', 'capture_le', 'note'])]
#[Hidden(['jeton'])]
class Precommande extends Model
{
    /** Livraison Yango : Abidjan uniquement. */
    public const COMMUNES = ['Abobo', 'Adjamé', 'Anyama', 'Attécoubé', 'Bingerville', 'Cocody', 'Koumassi', 'Marcory', 'Plateau', 'Port-Bouët', 'Songon', 'Treichville', 'Yopougon'];

    protected function casts(): array
    {
        return ['statut' => Statut::class, 'total' => 'integer', 'capture_le' => 'datetime'];
    }

    /** TEF- + 5 lettres sans ambiguïté (ni I, ni O), unique. */
    public static function nouvelleReference(): string
    {
        do {
            $ref = 'TEF-'.collect(range(1, 5))->map(fn () => 'ABCDEFGHJKLMNPQRSTUVWXYZ'[random_int(0, 23)])->implode('');
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function jetonValide(?string $jeton): bool
    {
        return is_string($jeton) && hash_equals($this->jeton, hash('sha256', $jeton));
    }

    /** Le client peut encore modifier sa commande tant qu'il n'a pas envoyé de capture. */
    public function modifiable(): bool
    {
        return $this->statut === Statut::EnAttente && ! $this->capture;
    }

    public function getAdresseAttribute(): string
    {
        return "{$this->quartier}, {$this->commune} (Abidjan)";
    }

    public function getWhatsappAttribute(): string
    {
        // 07 00 00 00 00 (Côte d'Ivoire, 10 chiffres) → 2250700000000
        $chiffres = preg_replace('/\D/', '', $this->telephone);

        return strlen($chiffres) === 10 ? '225'.$chiffres : $chiffres;
    }

    protected static function booted(): void
    {
        // Annuler rend les pièces au stock ; réactiver une précommande annulée les reprend.
        static::updating(function (Precommande $p) {
            if (! $p->isDirty('statut')) {
                return;
            }
            $avant = Statut::tryFrom((string) $p->getRawOriginal('statut'));
            $apres = $p->statut;
            if ($avant !== Statut::Annulee && $apres === Statut::Annulee) {
                $p->ajusterStock(+1);
            } elseif ($avant === Statut::Annulee && $apres !== Statut::Annulee) {
                $p->ajusterStock(-1);
            }
        });

        // supprimer une précommande encore active rend aussi ses pièces au stock
        static::deleting(function (Precommande $p) {
            if ($p->statut !== Statut::Annulee) {
                $p->ajusterStock(+1);
            }
        });
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(Ligne::class);
    }

    public function getNombrePiecesAttribute(): int
    {
        return $this->lignes->sum('quantite');
    }

    /** +1 : remet les pièces en stock, -1 : les reprend (sans descendre sous zéro). */
    public function ajusterStock(int $sens): void
    {
        DB::transaction(function () use ($sens) {
            foreach ($this->lignes as $l) {
                $stock = Stock::where('produit_id', $l->produit_id)->where('taille', $l->taille)->lockForUpdate()->first();
                if ($stock) {
                    $stock->update(['quantite' => max(0, $stock->quantite + $sens * $l->quantite)]);
                }
            }
        });
    }
}
