{{-- Retour du client après le paiement Jeko : payé, en cours de confirmation, ou échec --}}
@php $essai = (int) request('essai', 0); @endphp
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#0d0907">
<title>{{ $etat === 'paye' ? 'Merci !' : ($etat === 'echec' ? 'Paiement non abouti' : 'Paiement en cours') }} · Tiakolisé et fière</title>
@if ($etat === 'attente' && $essai < 12)
    {{-- Jeko confirme en quelques secondes : on revérifie tout seul --}}
    <meta http-equiv="refresh" content="4;url={{ route('paiement.retour', $p->reference) }}?essai={{ $essai + 1 }}">
@endif
@include('partials.icones')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo:wdth,wght@62..125,400..800&display=swap">
@vite(['resources/css/app.css'])
</head>
<body class="grid min-h-dvh place-items-center bg-nuit px-6 py-12 text-creme">
<main class="w-full max-w-md">
    <a href="/"><img src="{{ \App\Models\Media::url(\App\Models\Media::where('zone', 'logo')->value('fichier') ?? 'assets/logotiako.png') }}" alt="Tiakolisé et fière" class="h-16 w-auto -rotate-4"></a>

    @if ($etat === 'paye')
        <p class="mt-10 inline-flex rounded-full bg-ocre px-3 py-1.5 text-[11px] font-bold tracking-[.16em] text-nuit uppercase">✓ Paiement reçu</p>
        <h1 class="mt-4 font-display text-[64px] leading-[.9] uppercase">Merci,<br>c'est validé.</h1>
        <p class="mt-5 text-lg opacity-80">Ta précommande <strong class="font-display text-2xl tracking-wide text-ocre">{{ $p->reference }}</strong> ({{ fcfa($p->montant) }}, payée avec {{ $moyen }}) est confirmée. Tu reçois un e-mail de confirmation, et on te contacte sur WhatsApp pour caler la livraison Yango.</p>
        <a href="/" class="mt-8 flex h-14 items-center justify-center rounded-md bg-creme text-sm font-bold tracking-[.22em] text-nuit uppercase transition hover:bg-ocre">Retour au site</a>
        <script>try{ localStorage.removeItem("tk-panier"); }catch{}</script>
    @elseif ($etat === 'echec')
        <p class="mt-10 inline-flex rounded-full bg-rouge px-3 py-1.5 text-[11px] font-bold tracking-[.16em] text-creme uppercase">Paiement non abouti</p>
        <h1 class="mt-4 font-display text-[56px] leading-[.9] uppercase">Le paiement n'est pas passé.</h1>
        <p class="mt-5 text-lg opacity-80">Aucune précommande n'a été enregistrée et rien n'a été débité. Ton panier t'attend sur le site : tu peux réessayer, avec {{ $moyen }} ou un autre moyen.</p>
        <a href="/" class="mt-8 flex h-14 items-center justify-center rounded-md bg-rouge text-sm font-bold tracking-[.22em] text-creme uppercase transition hover:bg-creme hover:text-nuit">Réessayer</a>
    @else
        <p class="mt-10 inline-flex items-center gap-2 rounded-full bg-creme/10 px-3 py-1.5 text-[11px] font-bold tracking-[.16em] uppercase"><span class="size-2 animate-pulse rounded-full bg-ocre"></span>Confirmation en cours</p>
        <h1 class="mt-4 font-display text-[56px] leading-[.9] uppercase">On attend la confirmation de {{ $moyen }}.</h1>
        @if ($essai < 12)
            <p class="mt-5 text-lg opacity-80">Si tu as validé le paiement dans ton application, ça prend en général quelques secondes. Cette page se met à jour toute seule, ne la ferme pas.</p>
        @else
            <p class="mt-5 text-lg opacity-80">La confirmation prend plus de temps que prévu. Si tu as bien payé, ta précommande <strong class="text-ocre">{{ $p->reference }}</strong> sera validée automatiquement et tu recevras un e-mail. Un doute ? Écris-nous.</p>
            @if ($whatsapp)
                <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode("Bonjour ! J'ai payé ".fcfa($p->montant)." avec {$moyen} pour ma précommande {$p->reference}, mais je n'ai pas eu de confirmation.") }}" class="mt-8 flex h-14 items-center justify-center rounded-md bg-[#25d366] text-sm font-bold tracking-[.18em] text-nuit uppercase">Nous écrire sur WhatsApp</a>
            @endif
            <a href="{{ route('paiement.retour', $p->reference) }}" class="mt-3 flex h-12 items-center justify-center rounded-md border-2 border-creme/20 text-xs font-bold tracking-[.2em] uppercase">Vérifier à nouveau</a>
        @endif
    @endif
</main>
</body>
</html>
