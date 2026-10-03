<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#0d0907">
@include('partials.icones')
<title>Connexion · Tiakolisé et fière</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo:wdth,wght@62..125,400..800&display=swap">
@vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="grid min-h-dvh bg-nuit! lg:grid-cols-2">
    {{-- côté visuel : une vidéo du shooting --}}
    <div class="relative hidden overflow-hidden lg:block">
        <video src="/assets/shoot-6572.mp4" poster="/assets/shoot-6572.jpg" autoplay muted loop playsinline class="absolute inset-0 size-full object-cover"></video>
        <p class="absolute inset-x-10 bottom-10 font-display text-6xl leading-[.95] text-creme uppercase xl:text-7xl"><span class="text-ocre">Monétisez</span> les clips <span class="text-rouge">afro</span> francophones</p>
    </div>
    <div class="flex flex-col justify-center px-6 py-12 text-creme sm:px-16">
        <img src="/assets/logo-tiakolise.png" alt="Tiakolisé et fière" class="h-20 w-auto -rotate-4 self-start">
        <h1 class="mt-10 font-display text-5xl leading-none uppercase sm:text-6xl">Back office</h1>
        <p class="mt-3 text-creme/60">Précommandes, collection et stock.</p>

        <form method="post" action="{{ url('/admin/connexion') }}" class="mt-10 grid max-w-sm gap-5">
            @csrf
            @error('email')
                <p role="alert" class="rounded-xl bg-rouge px-4 py-3 text-sm font-semibold text-creme">{{ $message }}</p>
            @enderror
            <label>
                <span class="etiquette">E-mail</span>
                <input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="champ border-creme/15! bg-creme/5! text-creme focus:border-creme!">
            </label>
            <label>
                <span class="etiquette">Mot de passe</span>
                <input name="password" type="password" required autocomplete="current-password" class="champ border-creme/15! bg-creme/5! text-creme focus:border-creme!">
            </label>
            <label class="flex items-center gap-3 text-sm text-creme/80">
                <input type="checkbox" name="souvenir" value="1" class="size-5 accent-rouge"> Rester connecté·e
            </label>
            <button class="btn btn-rouge mt-2 py-5! hover:bg-creme! hover:text-nuit!">Se connecter →</button>
        </form>
    </div>
</body>
</html>
