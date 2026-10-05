@extends('mail.tk.layout', ['titre' => 'Tes pièces t\'attendent', 'bandeau' => 'Série limitée · tes pièces sont réservées', 'apercu' => "Ta précommande {$p->reference} est réservée : il ne manque plus que ton paiement Wave de ".fcfa($p->total)."."])

@section('contenu')
    <p style="margin:0;font-size:12px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:#c4743c;">Précommande {{ $p->reference }}</p>
    <h1 class="display grand" style="margin:8px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:50px;line-height:.95;font-weight:400;text-transform:uppercase;color:#0d0907;">Tes pièces t'attendent, {{ \Illuminate\Support\Str::of($p->nom)->explode(' ')->first() }}&nbsp;!</h1>
    <p style="margin:18px 0 0;font-size:17px;">On t'a mis tes pièces de côté, mais on n'a pas encore reçu ton paiement. C'est une <strong>série limitée</strong> : envoie ton paiement pour être sûr·e de les garder.</p>

    <div style="height:24px;line-height:24px;">&nbsp;</div>
    @include('mail.tk.articles')

    @if ($wave)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:26px;background:#1dc4f0;border-radius:14px;">
            <tr><td style="padding:24px 26px;color:#0d0907;">
                <p style="margin:0;font-size:11px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;">Montant à envoyer par Wave</p>
                <p class="display" style="margin:8px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:42px;line-height:1;">{{ fcfa($p->total) }}</p>
                <p style="margin:6px 0 0;font-size:16px;">au <strong style="font-size:20px;white-space:nowrap;">{{ $wave }}</strong></p>
                <p style="margin:14px 0 0;font-size:15px;">Puis envoie la <strong>capture d'écran</strong> de ton paiement avec ta référence <strong>{{ $p->reference }}</strong>. Déjà payé ? Envoie-nous juste la capture, et merci !</p>
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

    <p style="margin:24px 0 0;font-size:14px;color:#6b5f55;">Livraison Yango à {{ $p->adresse }}, course payée au livreur. Tu as changé d'avis ? Réponds simplement à cet e-mail ou écris-nous sur WhatsApp.</p>
    <p style="margin:24px 0 0;font-size:16px;">À très vite,<br><strong>L'équipe Tiakolisé et fière</strong></p>
@endsection
