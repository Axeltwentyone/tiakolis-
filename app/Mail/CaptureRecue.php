<?php

namespace App\Mail;

use App\Models\Precommande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** À l'équipe : un client vient d'envoyer la capture de son paiement Wave ou Orange Money (jointe au mail). */
class CaptureRecue extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Precommande $precommande) {}

    public function envelope(): Envelope
    {
        $p = $this->precommande;

        return new Envelope(subject: 'Nouveau paiement · '.fcfa($p->total)." · {$p->reference}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.tk.nouveau-paiement', with: [
            'p' => $this->precommande->loadMissing('lignes.produit'),
            'lien' => url('/admin/precommandes/'.$this->precommande->id),
        ]);
    }

    public function attachments(): array
    {
        $chemin = $this->precommande->capture;

        return $chemin ? [Attachment::fromStorageDisk('local', $chemin)->as($this->precommande->reference.'-capture.'.pathinfo($chemin, PATHINFO_EXTENSION))] : [];
    }
}
