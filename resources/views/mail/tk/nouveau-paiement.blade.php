@extends('mail.tk.layout', ['titre' => 'Nouveau paiement Wave', 'bandeau' => 'Nouveau paiement à valider', 'apercu' => "{$p->nom} a payé ".fcfa($p->total)." par Wave pour {$p->reference}. Vérifie et valide."])

@section('contenu')
    @php $capture = $p->capture && isset($message) && \Illuminate\Support\Facades\Storage::disk('local')->exists($p->capture)
        ? $message->embedData(\Illuminate\Support\Facades\Storage::disk('local')->get($p->capture), $p->reference.'.jpg', \Illuminate\Support\Facades\Storage::disk('local')->mimeType($p->capture))
        : null; @endphp

    <table role="presentation" cellpadding="0" cellspacing="0"><tr>
        <td style="background:#f2a33a;border-radius:999px;padding:6px 14px;font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:#0d0907;">Nouveau paiement</td>
    </tr></table>
    <p class="display grand" style="margin:14px 0 0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:60px;line-height:.95;color:#0d0907;">{{ fcfa($p->total) }}</p>
    <p style="margin:8px 0 0;font-size:17px;">par <strong>Wave</strong> · commande <strong>{{ $p->reference }}</strong></p>

    {{-- client --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;background:#0d0907;border-radius:14px;">
        <tr><td style="padding:20px 24px;color:#f6efe4;">
            <p class="display" style="margin:0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:26px;line-height:1.05;text-transform:uppercase;">{{ $p->nom }}</p>
            <p style="margin:6px 0 0;font-size:15px;color:#d9cbb4;">WhatsApp <a href="https://wa.me/{{ $p->whatsapp }}" style="color:#f2a33a;text-decoration:none;font-weight:800;">{{ $p->telephone }}</a>@if ($p->email) · {{ $p->email }}@endif</p>
            <p style="margin:4px 0 0;font-size:15px;color:#d9cbb4;">Livraison Yango · {{ $p->adresse }}</p>
        </td></tr>
    </table>

    {{-- capture --}}
    @if ($capture)
        <p style="margin:28px 0 10px;font-size:12px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;">Capture envoyée {{ $p->capture_le?->locale('fr')->isoFormat('[le] D MMMM [à] HH:mm') }}</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#1dc4f0;border-radius:14px;">
            <tr><td align="center" style="padding:16px;">
                <img src="{{ $capture }}" width="280" alt="Capture du paiement Wave" style="display:block;width:280px;max-width:100%;height:auto;border:0;border-radius:10px;background:#fff;">
            </td></tr>
        </table>
    @endif

    <div style="height:24px;line-height:24px;">&nbsp;</div>
    @include('mail.tk.articles')

    {{-- action --}}
    <p style="margin:24px 0 0;font-size:16px;">Vérifie dans ton application Wave que tu as bien reçu <strong>{{ fcfa($p->total) }}</strong>, puis valide le paiement : la commande passera en « Payée ».</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;"><tr>
        <td align="center" style="background:#0d0907;border-radius:8px;">
            <a href="{{ $lien }}" style="display:block;padding:18px 20px;font-size:14px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;color:#f6efe4;text-decoration:none;">Vérifier et valider →</a>
        </td>
    </tr></table>
    <p style="margin:12px 0 0;font-size:13px;color:#6b5f55;text-align:center;">La capture est aussi en pièce jointe.</p>
@endsection
