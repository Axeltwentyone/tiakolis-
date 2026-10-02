@extends('admin.layout', ['titre' => 'Collection'])

@section('contenu')
    @include('admin.partials.titre', [
        'surtitre' => 'Collection & stock',
        'titre' => 'Les pièces',
        'actions' => '<a href="'.route('admin.pieces.create').'" class="btn btn-rouge">+ Nouvelle pièce</a>',
    ])

    <p class="mb-6 max-w-2xl text-sm opacity-70">Le stock baisse à chaque précommande et remonte quand une précommande est annulée. L'ordre ici est l'ordre d'apparition sur le site.</p>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($pieces as $piece)
            @php $reste = $piece->stocks->sum('quantite'); @endphp
            <article @class(['overflow-hidden rounded-[20px] bg-white', 'opacity-60' => ! $piece->actif])>
                {{-- la pièce sur le papier terre cuite, comme sur le site --}}
                <a href="{{ route('admin.pieces.edit', $piece) }}" class="papier group relative block aspect-[1/.8] overflow-hidden">
                    <img src="{{ \App\Models\Produit::urlImage($piece->image_face) }}" alt="{{ $piece->libelle }}" class="absolute inset-0 size-full object-contain p-6 drop-shadow-[0_20px_20px_rgba(60,20,5,.4)] transition duration-500 group-hover:opacity-0">
                    <img src="{{ \App\Models\Produit::urlImage($piece->image_dos) }}" alt="" class="absolute inset-0 size-full object-contain p-6 opacity-0 drop-shadow-[0_20px_20px_rgba(60,20,5,.4)] transition duration-500 group-hover:opacity-100">
                    <span class="absolute top-3 left-3 rounded-full bg-nuit px-2.5 py-1 font-display text-sm text-creme tabular-nums">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    @if ($reste === 0)
                        <span class="absolute top-3 right-3 rounded-full bg-rouge px-3 py-1 text-[11px] font-bold tracking-[.14em] text-creme uppercase">Épuisé</span>
                    @elseif (! $piece->actif)
                        <span class="absolute top-3 right-3 rounded-full bg-creme px-3 py-1 text-[11px] font-bold tracking-[.14em] uppercase">Masquée</span>
                    @endif
                </a>
                <div class="grid gap-3 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-display text-2xl leading-none uppercase">{{ $piece->nom }}</h2>
                            <p class="mt-1 text-xs font-semibold tracking-[.14em] uppercase opacity-60">{{ $piece->couleur }} · {{ $piece->type }}</p>
                        </div>
                        <p class="font-display text-2xl whitespace-nowrap tabular-nums">{{ fcfa($piece->prix) }}</p>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        @include('admin.partials.stock')
                        <span @class(['text-sm font-bold tabular-nums whitespace-nowrap', 'text-rouge' => $reste <= 10])>{{ $reste }} restantes</span>
                    </div>
                    <div class="flex gap-2 border-t-2 border-nuit/5 pt-3">
                        <a href="{{ route('admin.pieces.edit', $piece) }}" class="btn btn-nuit flex-1">Modifier</a>
                        <form method="post" action="{{ route('admin.pieces.visibilite', $piece) }}">
                            @csrf @method('patch')
                            <button class="btn btn-ligne" title="{{ $piece->actif ? 'Masquer du site' : 'Afficher sur le site' }}">{{ $piece->actif ? 'Masquer' : 'Afficher' }}</button>
                        </form>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endsection
