<?php

namespace App\Mail;

use App\Models\Precommande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Au client : son paiement (Wave ou Orange Money) est validé, la précommande est confirmée. C'est le seul e-mail qu'il reçoit (rien avant le paiement). */
class PaiementValide extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Precommande $precommande) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "C'est validé ! Ta précommande {$this->precommande->reference} est confirmée");
    }

    public function content(): Content
    {
        $whatsapp = preg_replace('/\D/', '', (string) config('services.precommandes.whatsapp_numero'));

        return new Content(view: 'mail.tk.paiement-valide', with: [
            'p' => $this->precommande->loadMissing('lignes.produit'),
            'whatsapp' => $whatsapp ?: null,
        ]);
    }
}
