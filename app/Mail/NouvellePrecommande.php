<?php

namespace App\Mail;

use App\Models\Precommande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** À l'équipe (NOTIFICATION_EMAIL) : une nouvelle précommande vient d'arriver. */
class NouvellePrecommande extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Precommande $precommande) {}

    public function envelope(): Envelope
    {
        $p = $this->precommande;

        return new Envelope(
            subject: "Nouvelle commande {$p->reference} · {$p->nombre_pieces} pièce(s) · ".fcfa($p->total),
            replyTo: $p->email ? [new Address($p->email, $p->nom)] : [], // « Répondre » écrit directement au client
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.tk.nouvelle-commande', with: [
            'p' => $this->precommande->loadMissing('lignes.produit'),
            'lien' => url('/admin/precommandes/'.$this->precommande->id),
        ]);
    }
}
