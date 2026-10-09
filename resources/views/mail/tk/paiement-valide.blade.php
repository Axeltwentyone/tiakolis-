@extends('mail.tk.layout', ['titre' => 'Paiement confirmé', 'bandeau' => 'Paiement confirmé', 'apercu' => "On a bien reçu tes ".fcfa($p->total)." : ta commande {$p->reference} est confirmée."])

@section('contenu')
    <table role="presentation" cellpadding="0" cellspacing="0"><tr>
        <td style="background:#0d0907;border-radius:999px;padding:6px 14px;font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:#f2a33a;">✓ Paiement reçu</td>
    </tr></table>
    <h1 class="display grand" style="margin:14px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:54px;line-height:.95;font-weight:400;text-transform:uppercase;color:#0d0907;">C'est validé, {{ \Illuminate\Support\Str::of($p->nom)->explode(' ')->first() }}&nbsp;!</h1>
    <p style="margin:18px 0 0;font-size:17px;">On a bien reçu ton paiement de <strong>{{ fcfa($p->total) }}</strong>. Ta commande <strong>{{ $p->reference }}</strong> est confirmée : tes pièces sont à toi.</p>

    <div style="height:26px;line-height:26px;">&nbsp;</div>
    @include('mail.tk.articles')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td class="display" style="padding:10px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:30px;text-transform:uppercase;">Payé</td>
        <td align="right" class="display" style="padding:10px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:30px;white-space:nowrap;">{{ fcfa($p->total) }}</td>
    </tr></table>

    {{-- la suite : livraison --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:28px;background:#c4743c;border-radius:14px;">
        <tr><td style="padding:22px 26px;color:#0d0907;">
            <p style="margin:0;font-size:11px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;">Et maintenant ?</p>
            <p style="margin:8px 0 0;font-size:16px;">On te contacte sur <strong>WhatsApp au {{ $p->telephone }}</strong> pour caler la livraison Yango à <strong>{{ $p->adresse }}</strong>.</p>
            <p style="margin:8px 0 0;font-size:15px;">La course se paie directement au livreur à la réception, en espèces ou par Wave.</p>
        </td></tr>
    </table>
    @if ($whatsapp)
        <p style="margin:18px 0 0;font-size:15px;">Une question ? Écris-nous sur <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode("Bonjour, c'est à propos de ma commande {$p->reference}.") }}" style="color:#e3342a;font-weight:800;">WhatsApp</a>.</p>
    @endif

    <p style="margin:28px 0 0;font-size:16px;">Merci pour ton soutien, et porte-la fièrement.<br><strong>L'équipe Tiakolisé et fière</strong></p>
@endsection
