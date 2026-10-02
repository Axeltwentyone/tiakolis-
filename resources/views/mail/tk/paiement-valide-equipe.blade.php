@extends('mail.tk.layout', ['titre' => 'Paiement validé', 'bandeau' => 'Paiement validé', 'apercu' => "{$p->reference} est payée".($parQui ? ", validée par {$parQui}" : '').'.'])

@section('contenu')
    <table role="presentation" cellpadding="0" cellspacing="0"><tr>
        <td style="background:#0d0907;border-radius:999px;padding:6px 14px;font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:#f2a33a;">✓ Payée</td>
    </tr></table>
    <p class="display" style="margin:14px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:44px;line-height:.95;text-transform:uppercase;">{{ $p->reference }} · {{ fcfa($p->total) }}</p>
    <p style="margin:10px 0 0;font-size:17px;">Paiement validé{{ $parQui ? " par {$parQui}" : '' }}. Pas besoin de la revérifier.</p>
    <p style="margin:6px 0 0;font-size:15px;color:#6b5f55;"><strong style="color:#0d0907;">{{ $p->nom }}</strong> · WhatsApp {{ $p->telephone }} · Livraison Yango {{ $p->adresse }}</p>
    @if ($p->email)
        <p style="margin:6px 0 0;font-size:14px;color:#6b5f55;">Le client a reçu l'e-mail « C'est validé ! ».</p>
    @else
        <p style="margin:6px 0 0;font-size:14px;color:#6b5f55;">Pas d'e-mail client : préviens-le sur WhatsApp.</p>
    @endif

    <div style="height:20px;line-height:20px;">&nbsp;</div>
    @include('mail.tk.articles')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;"><tr>
        <td align="center" style="border:2px solid #0d0907;border-radius:8px;">
            <a href="{{ $lien }}" style="display:block;padding:15px 20px;font-size:13px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;color:#0d0907;text-decoration:none;">Préparer la livraison</a>
        </td>
    </tr></table>
@endsection
