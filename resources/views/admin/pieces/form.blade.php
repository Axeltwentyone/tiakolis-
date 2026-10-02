@php $nouvelle = ! $piece->exists; @endphp
@extends('admin.layout', ['titre' => $nouvelle ? 'Nouvelle pièce' : $piece->libelle])

@section('contenu')
    <a href="{{ route('admin.pieces.index') }}" class="mb-4 inline-block text-xs font-bold tracking-[.14em] uppercase underline underline-offset-4 opacity-70 hover:opacity-100">← Collection</a>
    @include('admin.partials.titre', ['surtitre' => $nouvelle ? 'Ajouter à la collection' : 'Modifier', 'titre' => $nouvelle ? 'Nouvelle pièce' : $piece->nom.' · '.$piece->couleur])

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-2xl bg-rouge px-5 py-4 text-sm font-semibold text-creme">
            @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    <form method="post" enctype="multipart/form-data" action="{{ $nouvelle ? route('admin.pieces.store') : route('admin.pieces.update', $piece) }}" class="grid gap-6 lg:grid-cols-[1fr_1.2fr]" data-piece-form @if ($nouvelle) data-nouvelle @endif>
        @csrf
        @unless ($nouvelle) @method('put') @endunless

        {{-- Photos : aperçu sur le papier terre cuite --}}
        <section class="grid grid-cols-2 content-start gap-3 sm:gap-4 lg:sticky lg:top-12">
            @foreach (['image_face' => 'Face', 'image_dos' => 'Dos'] as $champ => $label)
                <label class="group block cursor-pointer">
                    <span class="etiquette">Photo {{ $label }}</span>
                    <span class="papier relative grid aspect-square place-items-center overflow-hidden rounded-[20px] ring-nuit/0 transition group-hover:ring-4 group-hover:ring-nuit">
                        <img data-apercu="{{ $champ }}" src="{{ \App\Models\Produit::urlImage($piece->{$champ}) }}" alt="" @class(['absolute inset-0 size-full object-contain p-5 drop-shadow-[0_20px_20px_rgba(60,20,5,.4)]', 'hidden' => ! $piece->{$champ}])>
                        <span class="relative rounded-full bg-nuit/85 px-4 py-2 text-xs font-bold tracking-[.14em] text-creme uppercase opacity-0 transition group-hover:opacity-100 {{ $piece->{$champ} ? '' : 'opacity-100!' }}">{{ $piece->{$champ} ? 'Changer' : 'Choisir une photo' }}</span>
                    </span>
                    <input type="file" name="{{ $champ }}" accept="image/webp,image/png,image/jpeg" class="sr-only" data-photo="{{ $champ }}" @if ($nouvelle) required @endif>
                </label>
            @endforeach
            <p class="col-span-2 text-sm opacity-60">Format conseillé : .webp ou .png sur fond transparent, environ 1200 px de large, 4 Mo maximum.</p>
        </section>

        <div class="grid content-start gap-6">
            <section class="carte grid gap-4 sm:grid-cols-2">
                <h2 class="font-display text-2xl uppercase sm:col-span-2">La pièce</h2>
                <label><span class="etiquette">Nom</span><input name="nom" value="{{ old('nom', $piece->nom) }}" required maxlength="60" class="champ" data-slug-source></label>
                <label><span class="etiquette">Couleur</span><input name="couleur" value="{{ old('couleur', $piece->couleur) }}" required maxlength="30" class="champ" data-slug-source></label>
                <label><span class="etiquette">Type</span><input name="type" value="{{ old('type', $piece->type) }}" required maxlength="60" class="champ"></label>
                <label><span class="etiquette">Prix (FCFA)</span><input name="prix" type="number" min="0" step="500" inputmode="numeric" value="{{ old('prix', $piece->prix) }}" required class="champ tabular-nums"></label>
                <label>
                    <span class="etiquette">Identifiant</span>
                    <input name="slug" value="{{ old('slug', $piece->slug) }}" required maxlength="60" pattern="[A-Za-z0-9_-]+" class="champ font-mono text-sm" data-slug>
                    <span class="mt-1 block text-xs opacity-60">Utilisé par les paniers des clients : ne plus le changer une fois en vente.</span>
                </label>
                <label><span class="etiquette">Position sur le site</span><input name="ordre" type="number" min="0" value="{{ old('ordre', $piece->ordre) }}" class="champ tabular-nums"></label>
                <label class="flex items-center gap-3 sm:col-span-2">
                    <input type="checkbox" name="actif" value="1" @checked(old('actif', $piece->actif)) class="size-5 accent-rouge">
                    <span class="font-semibold">Visible sur le site</span>
                </label>
            </section>

            <section class="carte">
                <h2 class="font-display text-2xl uppercase">Stock par taille</h2>
                <p class="mt-1 text-sm opacity-60">Pièces encore disponibles à la précommande. 0 = taille épuisée.</p>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach (\App\Models\Produit::TAILLES as $t)
                        @php $actuel = $piece->stocks->firstWhere('taille', $t)?->quantite ?? 0; @endphp
                        <label class="rounded-2xl bg-nuit/5 p-3 text-center">
                            <span class="block font-display text-3xl">{{ $t }}</span>
                            <input type="hidden" name="stock_initial[{{ $t }}]" value="{{ $actuel }}">
                            <input name="stock[{{ $t }}]" type="number" min="0" inputmode="numeric" value="{{ old("stock.$t", $actuel) }}" class="champ mt-2 text-center text-lg font-bold tabular-nums" aria-label="Stock taille {{ $t }}">
                        </label>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-3">
                <button class="btn btn-rouge py-5! sm:px-10!">{{ $nouvelle ? 'Ajouter la pièce' : 'Enregistrer' }}</button>
                <a href="{{ route('admin.pieces.index') }}" class="btn btn-ligne py-5!">Annuler</a>
            </div>
        </div>
    </form>

    @unless ($nouvelle)
        <form method="post" action="{{ route('admin.pieces.destroy', $piece) }}" class="mt-10 border-t-2 border-nuit/10 pt-6">
            @csrf @method('delete')
            <button data-confirme="Supprimer {{ $piece->libelle }} ? (Impossible si elle a déjà des précommandes : masque-la plutôt.)" class="text-xs font-bold tracking-[.14em] uppercase text-rouge underline underline-offset-4">Supprimer cette pièce</button>
        </form>
    @endunless
@endsection
