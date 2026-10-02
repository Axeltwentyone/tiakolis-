<?php

namespace App\Mail;

use App\Models\Precommande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Au client : merci pour la précommande, récapitulatif et instructions Wave (gabarit aux couleurs du site). */
class PrecommandeRecue extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Precommande $precommande) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Merci pour ta précommande ! · {$this->precommande->reference}");
    }

    public function content(): Content
    {
        $whatsapp = preg_replace('/\D/', '', (string) config('services.precommandes.whatsapp_numero'));

        return new Content(view: 'mail.tk.merci-client', with: [
            'p' => $this->precommande->loadMissing('lignes.produit'),
            'wave' => config('services.precommandes.wave_numero'),
            'whatsapp' => $whatsapp ?: null,
        ]);
    }
}
