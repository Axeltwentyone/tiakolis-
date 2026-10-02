@extends('mail.tk.layout', ['titre' => 'Merci pour ta précommande', 'apercu' => "Ta précommande {$p->reference} est réservée. Il ne reste plus que le paiement Wave."])

@section('contenu')
    <p style="margin:0;font-size:12px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:#c4743c;">Précommande {{ $p->reference }}</p>
    <h1 class="display grand" style="margin:8px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:54px;line-height:.95;font-weight:400;text-transform:uppercase;color:#0d0907;">Merci {{ \Illuminate\Support\Str::of($p->nom)->explode(' ')->first() }}&nbsp;!</h1>
    <p style="margin:18px 0 0;font-size:17px;">Tes pièces sont <strong>réservées</strong>. C'est une série limitée : merci de faire partie de l'aventure. Fait par nous, pour nous.</p>

    <div style="height:26px;line-height:26px;">&nbsp;</div>
    @include('mail.tk.articles')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:12px 0 4px;font-size:15px;">Livraison</td>
            <td align="right" style="padding:12px 0 4px;font-size:15px;">Course Yango payée au livreur</td>
        </tr>
        <tr>
            <td class="display" style="padding:6px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:30px;text-transform:uppercase;">Total</td>
            <td align="right" class="display" style="padding:6px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:30px;white-space:nowrap;">{{ fcfa($p->total) }}</td>
        </tr>
    </table>

    @if ($wave)
        {{-- encart Wave, comme à l'étape paiement du site --}}
        <div style="height:28px;line-height:28px;">&nbsp;</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#1dc4f0;border-radius:14px;">
            <tr><td style="padding:24px 26px;color:#0d0907;">
                <p style="margin:0;font-size:11px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;">Pour confirmer ta commande</p>
                <p class="display" style="margin:8px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:40px;line-height:1;">{{ fcfa($p->total) }}</p>
                <p style="margin:6px 0 0;font-size:16px;">à envoyer par <strong>Wave</strong> au <strong style="font-size:20px;white-space:nowrap;">{{ $wave }}</strong></p>
                <p style="margin:14px 0 0;font-size:15px;">Puis envoie la <strong>capture d'écran</strong> de ton paiement sur le site ou sur WhatsApp, avec ta référence <strong>{{ $p->reference }}</strong>. Si c'est déjà fait : merci, on vérifie !</p>
                @if ($whatsapp)
                    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:18px;"><tr>
                        <td style="background:#25d366;border-radius:8px;">
                            <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode("Bonjour ! Voici la capture de mon paiement Wave de ".fcfa($p->total)." pour la commande {$p->reference}.") }}" style="display:inline-block;padding:14px 22px;font-size:13px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#0d0907;text-decoration:none;">Envoyer ma capture sur WhatsApp</a>
                        </td>
                    </tr></table>
                @endif
            </td></tr>
        </table>
    @endif

    <div style="height:28px;line-height:28px;">&nbsp;</div>
    <p style="margin:0;font-size:12px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;">Livraison Yango</p>
    <p style="margin:6px 0 0;font-size:16px;">{{ $p->adresse }}@if ($p->note_client)<br><span style="color:#6b5f55;">« {{ $p->note_client }} »</span>@endif</p>
    <p style="margin:6px 0 0;font-size:14px;color:#6b5f55;">On te contacte sur WhatsApp au {{ $p->telephone }} pour caler la livraison. La course se paie directement au livreur, en espèces ou par Wave.</p>

    <p style="margin:30px 0 0;font-size:16px;">À très vite,<br><strong>L'équipe Tiakolisé et fière</strong></p>
@endsection
