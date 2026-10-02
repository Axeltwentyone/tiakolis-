<?php

namespace App\Mail;

use App\Models\Precommande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** À l'équipe : un admin vient de valider un paiement (pour que les autres ne le revérifient pas). */
class PaiementValideEquipe extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Precommande $precommande, public ?string $parQui = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Paiement validé · '.fcfa($this->precommande->total)." · {$this->precommande->reference}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.tk.paiement-valide-equipe', with: [
            'p' => $this->precommande->loadMissing('lignes.produit'),
            'parQui' => $this->parQui,
            'lien' => url('/admin/precommandes/'.$this->precommande->id),
        ]);
    }
}
