@extends('admin.layout', ['titre' => 'Photos du site'])

@section('contenu')
    @include('admin.partials.titre', ['surtitre' => 'Ce que voient les visiteurs', 'titre' => 'Photos du site'])
    <p class="-mt-4 mb-8 max-w-2xl text-sm opacity-70">Chaque changement est en ligne dès que tu cliques sur « Enregistrer ». Photos en jpg, png ou webp, vidéos en mp4, 10 Mo maximum. Les photos des t-shirts se changent dans <a href="{{ route('admin.pieces.index') }}" class="font-semibold underline underline-offset-2">Collection</a>.</p>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-2xl bg-rouge px-5 py-4 text-sm font-semibold text-creme">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    {{-- 1. Logo + image de partage --}}
    <div class="grid gap-4 lg:grid-cols-2">
        @if ($logo = $m['logo'][0] ?? null)
            <section class="carte grid content-start gap-4">
                <div>
                    <h2 class="font-display text-2xl uppercase">Logo</h2>
                    <p class="text-sm opacity-60">En haut du site, au-dessus de la grille et en bas de page. PNG sur fond transparent conseillé.</p>
                </div>
                <div id="apercu-{{ $logo->id }}" class="apercu apercu--contain h-44 overflow-hidden rounded-2xl bg-nuit p-6">@include('admin.partials.media-apercu', ['media' => $logo])</div>
                <form method="post" enctype="multipart/form-data" action="{{ route('admin.site.remplacer', $logo) }}" class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    @csrf @method('put')
                    @include('admin.partials.fichier', ['name' => 'fichier', 'label' => 'Choisir un nouveau logo', 'accept' => 'image/png,image/webp,image/jpeg', 'cible' => 'apercu-'.$logo->id, 'requis' => true])
                    <button class="btn btn-nuit">Enregistrer</button>
                </form>
            </section>
        @endif
        @if ($partage = $m['partage'][0] ?? null)
            <section class="carte grid content-start gap-4">
                <div>
                    <h2 class="font-display text-2xl uppercase">Image de partage</h2>
                    <p class="text-sm opacity-60">Affichée sous le lien sur WhatsApp, Instagram, Facebook. Format 1200 × 630 px.</p>
                </div>
                <div id="apercu-{{ $partage->id }}" class="apercu aspect-[1200/630] overflow-hidden rounded-2xl bg-nuit">@include('admin.partials.media-apercu', ['media' => $partage])</div>
                <form method="post" enctype="multipart/form-data" action="{{ route('admin.site.remplacer', $partage) }}" class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    @csrf @method('put')
                    @include('admin.partials.fichier', ['name' => 'fichier', 'label' => 'Choisir une nouvelle image', 'accept' => 'image/jpeg,image/png,image/webp', 'cible' => 'apercu-'.$partage->id, 'requis' => true])
                    <button class="btn btn-nuit">Enregistrer</button>
                </form>
                <p class="text-xs opacity-60">WhatsApp garde l'ancien aperçu en mémoire quelque temps : pour tester, partage le lien avec <code>?v=2</code> à la fin.</p>
            </section>
        @endif
    </div>

    {{-- 2. Premier écran : 4 colonnes --}}
    <section class="mt-10">
        <h2 class="font-display text-3xl uppercase">Premier écran</h2>
        <p class="mt-1 mb-4 text-sm opacity-60">Les 4 colonnes en haut du site (2 sur téléphone). Vidéo courte en mp4 (quelques secondes, sans son) ou photo, format vertical.</p>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($m['hero'] ?? [] as $col)
                <form method="post" enctype="multipart/form-data" action="{{ route('admin.site.remplacer', $col) }}" class="carte grid content-start gap-3 p-4!">
                    @csrf @method('put')
                    <div id="apercu-{{ $col->id }}" class="apercu relative aspect-[9/14] overflow-hidden rounded-2xl bg-nuit">
                        @include('admin.partials.media-apercu', ['media' => $col])
                    </div>
                    <p class="surtitre">Colonne {{ $loop->iteration }}@if ($loop->iteration > 2) <span class="normal-case tracking-normal">· ordinateur seulement</span>@endif</p>
                    <label><span class="etiquette">Légende</span><input name="legende" value="{{ old('legende', $col->legende) }}" maxlength="40" class="champ h-11!" placeholder="ex. Dos · Warning"></label>
                    @include('admin.partials.fichier', ['name' => 'fichier', 'label' => 'Vidéo ou photo', 'accept' => 'video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/webp', 'cible' => 'apercu-'.$col->id])
                    @include('admin.partials.fichier', ['name' => 'poster', 'label' => 'Image d\'attente (vidéo)', 'accept' => 'image/jpeg,image/png,image/webp'])
                    <button class="btn btn-nuit">Enregistrer</button>
                </form>
            @endforeach
        </div>
        <p class="mt-3 text-xs opacity-60">L'image d'attente s'affiche le temps que la vidéo charge : prends une capture de la vidéo.</p>
    </section>

    {{-- 3. Grille --}}
    <section class="mt-10">
        <h2 class="font-display text-3xl uppercase">Grille</h2>
        <p class="mt-1 mb-4 text-sm opacity-60">La grille sous la collection. Une case « zappe » entre ces images (idéal : des captures du clip Mélo Décalé). Vidéo 1 = case « Le shooting », vidéo 2 = grande case « Mélo Décalé ». La photo de la case « Comment ça marche » est celle de la colonne 2 du premier écran.</p>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($m['tv_video'] ?? [] as $tv)
                <form method="post" enctype="multipart/form-data" action="{{ route('admin.site.remplacer', $tv) }}" class="carte grid gap-3 p-4! sm:grid-cols-[180px_1fr]">
                    @csrf @method('put')
                    <div id="apercu-{{ $tv->id }}" class="apercu aspect-[4/3] overflow-hidden rounded-xl bg-nuit">@include('admin.partials.media-apercu', ['media' => $tv])</div>
                    <div class="grid content-start gap-2">
                        <p class="surtitre">{{ $loop->iteration === 1 ? 'Vidéo · case « Le shooting »' : 'Vidéo · case « Mélo Décalé »' }}</p>
                        @include('admin.partials.fichier', ['name' => 'fichier', 'label' => 'Nouvelle vidéo (mp4)', 'accept' => 'video/mp4,video/webm,video/quicktime', 'cible' => 'apercu-'.$tv->id])
                        @include('admin.partials.fichier', ['name' => 'poster', 'label' => 'Image d\'attente', 'accept' => 'image/jpeg,image/png,image/webp'])
                        <button class="btn btn-nuit">Enregistrer</button>
                    </div>
                </form>
            @endforeach
        </div>

        <div class="carte mt-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <p class="surtitre">Images qui zappent · {{ count($m['tv_image'] ?? []) }}</p>
                <form method="post" enctype="multipart/form-data" action="{{ route('admin.site.tv') }}" class="flex flex-wrap gap-2">
                    @csrf
                    <div class="w-64 max-w-full">@include('admin.partials.fichier', ['name' => 'images', 'label' => 'Choisir des images', 'accept' => 'image/jpeg,image/png,image/webp', 'multiple' => true, 'requis' => true])</div>
                    <button class="btn btn-rouge">+ Ajouter</button>
                </form>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-7">
                @foreach ($m['tv_image'] ?? [] as $img)
                    <div class="group relative aspect-[4/3] overflow-hidden rounded-xl bg-nuit">
                        <img src="{{ $img->url }}" alt="" loading="lazy" class="size-full object-cover">
                        <form method="post" action="{{ route('admin.site.supprimer', $img) }}" class="absolute top-1.5 right-1.5">
                            @csrf @method('delete')
                            <button data-confirme="Retirer cette image de la grille ?" class="grid size-8 place-items-center rounded-full bg-nuit/80 text-creme transition hover:bg-rouge" aria-label="Retirer cette image">✕</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
