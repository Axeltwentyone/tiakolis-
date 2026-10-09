<?php

namespace App\Mail;

use App\Models\Precommande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Au client en attente de paiement : ses pièces sont réservées, il reste à payer (Wave ou Orange Money). */
class RelancePaiement extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Precommande $precommande) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tes pièces t'attendent · {$this->precommande->reference}");
    }

    public function content(): Content
    {
        $whatsapp = preg_replace('/\D/', '', (string) config('services.precommandes.whatsapp_numero'));

        return new Content(view: 'mail.tk.relance', with: [
            'p' => $this->precommande->loadMissing('lignes.produit'),
            'wave' => config('services.precommandes.wave_numero'),
            'lienWave' => str_replace('{montant}', (string) $this->precommande->total, (string) config('services.precommandes.wave_lien')) ?: null,
            'lienOm' => str_replace('{montant}', (string) $this->precommande->total, (string) config('services.precommandes.om_lien')) ?: null,
            'whatsapp' => $whatsapp ?: null,
        ]);
    }
}
