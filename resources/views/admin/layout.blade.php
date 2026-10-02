@php
    $aTraiter = \App\Models\Precommande::where('statut', \App\Enums\Statut::AVerifier)->count(); // captures Wave à vérifier
    $liens = [
        ['admin.tableau', 'admin.tableau', 'Tableau de bord', 'M3 12 12 4l9 8M5 10v10h14V10'],
        ['admin.precommandes.index', 'admin.precommandes.*', 'Précommandes', 'M4 7h16M4 12h16M4 17h10'],
        ['admin.pieces.index', 'admin.pieces.*', 'Collection', 'M8 4 4 7l2 3 2-1v11h8V9l2 1 2-3-4-3c0 1.5-1.8 2.5-4 2.5S8 5.5 8 4Z'],
    ];
@endphp
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#0d0907">
<title>{{ $titre ?? 'Back office' }} · Tiakolisé et fière</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo:wdth,wght@62..125,400..800&display=swap">
@vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="min-h-dvh">

{{-- Barre latérale (ordinateur) --}}
<aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-nuit px-5 pt-8 pb-6 text-creme lg:flex">
    <a href="{{ route('admin.tableau') }}" class="block px-2"><img src="/assets/logo-tiakolise.png" alt="Tiakolisé et fière" class="h-16 w-auto -rotate-4"></a>
    <p class="mt-3 px-2 text-[11px] font-bold tracking-[.2em] text-creme/50 uppercase">Back office</p>
    <nav class="mt-10 grid gap-1">
        @foreach ($liens as [$route, $motif, $label, $icone])
            <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-full px-4 py-3 text-sm font-bold tracking-[.08em] uppercase transition', 'bg-creme text-nuit' => request()->routeIs($motif), 'hover:bg-creme/10' => ! request()->routeIs($motif)])>
                <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icone }}"/></svg>
                <span class="flex-1">{{ $label }}</span>
                @if ($route === 'admin.precommandes.index' && $aTraiter)
                    <span class="grid min-w-6 place-items-center rounded-full bg-rouge px-1.5 py-0.5 text-[11px] tracking-normal text-creme tabular-nums">{{ $aTraiter }}</span>
                @endif
            </a>
        @endforeach
    </nav>
    <div class="mt-auto grid gap-2 border-t border-creme/15 pt-5 text-sm">
        <a href="/" target="_blank" class="rounded-full px-4 py-2 font-semibold text-creme/70 hover:text-creme">Voir le site ↗</a>
        <form method="post" action="{{ route('admin.deconnexion') }}">@csrf
            <button class="w-full rounded-full px-4 py-2 text-left font-semibold text-creme/70 hover:text-creme">Se déconnecter · {{ auth()->user()->name }}</button>
        </form>
    </div>
</aside>

{{-- En-tête (téléphone) --}}
<header class="sticky top-0 z-30 flex items-center justify-between bg-nuit px-4 pt-[calc(env(safe-area-inset-top,0px)+10px)] pb-2.5 text-creme lg:hidden">
    <a href="{{ route('admin.tableau') }}"><img src="/assets/logo-tiakolise.png" alt="Tiakolisé et fière" class="h-10 w-auto -rotate-4"></a>
    <div class="flex items-center gap-1">
        <a href="/" target="_blank" class="rounded-full px-3 py-2 text-xs font-bold tracking-[.12em] uppercase text-creme/70">Site ↗</a>
        <form method="post" action="{{ route('admin.deconnexion') }}">@csrf
            <button class="rounded-full px-3 py-2 text-xs font-bold tracking-[.12em] uppercase text-creme/70">Sortir</button>
        </form>
    </div>
</header>

<main class="px-4 pt-6 pb-32 sm:px-8 lg:ml-64 lg:px-12 lg:pt-12 lg:pb-16">
    <div class="mx-auto max-w-6xl">
        @if (session('ok'))
            <p data-flash role="status" class="mb-6 flex items-center gap-3 rounded-2xl bg-nuit px-5 py-4 text-sm font-semibold text-creme">
                <span class="grid size-6 shrink-0 place-items-center rounded-full bg-ocre text-nuit">✓</span>{{ session('ok') }}
            </p>
        @endif
        @yield('contenu')
    </div>
</main>

{{-- Barre d'onglets (téléphone) --}}
<nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-3 border-t-2 border-nuit bg-creme pb-[env(safe-area-inset-bottom,0px)] lg:hidden">
    @foreach ($liens as [$route, $motif, $label, $icone])
        <a href="{{ route($route) }}" @class(['relative grid justify-items-center gap-1 pt-2.5 pb-2 text-[10px] font-bold tracking-[.1em] uppercase', 'text-rouge' => request()->routeIs($motif), 'text-nuit/60' => ! request()->routeIs($motif)])>
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icone }}"/></svg>
            {{ $label === 'Tableau de bord' ? 'Accueil' : $label }}
            @if ($route === 'admin.precommandes.index' && $aTraiter)
                <span class="absolute top-1.5 left-[calc(50%+6px)] grid min-w-5 place-items-center rounded-full bg-rouge px-1 text-[10px] tracking-normal text-creme tabular-nums">{{ $aTraiter }}</span>
            @endif
        </a>
    @endforeach
</nav>
</body>
</html>
