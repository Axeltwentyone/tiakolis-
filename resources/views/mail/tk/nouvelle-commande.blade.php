@extends('mail.tk.layout', ['titre' => 'Nouvelle commande', 'bandeau' => 'Nouvelle commande', 'apercu' => "{$p->nom} vient de commander ({$p->reference}). Paiement Wave en attente."])

@section('contenu')
    <table role="presentation" cellpadding="0" cellspacing="0"><tr>
        <td style="background:#0d0907;border-radius:999px;padding:6px 14px;font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:#f6efe4;">Paiement en attente</td>
    </tr></table>
    <p class="display" style="margin:14px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:44px;line-height:.95;text-transform:uppercase;">{{ $p->reference }}</p>
    <p style="margin:8px 0 0;font-size:17px;"><strong>{{ $p->nom }}</strong> · WhatsApp {{ $p->telephone }} · {{ $p->commune }}</p>

    <div style="height:22px;line-height:22px;">&nbsp;</div>
    @include('mail.tk.articles')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td class="display" style="padding:10px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:26px;text-transform:uppercase;">À recevoir sur Wave</td>
        <td align="right" class="display" style="padding:10px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:26px;white-space:nowrap;">{{ fcfa($p->total) }}</td>
    </tr></table>

    <p style="margin:22px 0 0;font-size:15px;color:#6b5f55;">Les pièces sont réservées. Tu recevras un autre e-mail dès que le client envoie la capture de son paiement.</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;"><tr>
        <td align="center" style="border:2px solid #0d0907;border-radius:8px;">
            <a href="{{ $lien }}" style="display:block;padding:15px 20px;font-size:13px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;color:#0d0907;text-decoration:none;">Voir la commande</a>
        </td>
    </tr></table>
@endsection
