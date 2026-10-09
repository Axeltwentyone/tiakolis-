@extends('admin.layout', ['titre' => 'Précommandes'])

@section('contenu')
    @include('admin.partials.titre', [
        'surtitre' => $precommandes->total().' résultat'.($precommandes->total() > 1 ? 's' : ''),
        'titre' => 'Précommandes',
        'actions' => '<a href="'.e(route('admin.precommandes.export', request()->query())).'" class="btn btn-ligne">Exporter Excel ↓</a>',
    ])

    {{-- Filtres par statut --}}
    @php $tous = $compteurs->sum(); @endphp
    <nav class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="Filtrer par statut">
        <a href="{{ route('admin.precommandes.index', array_filter(['q' => $q])) }}" @class(['btn py-2.5!', 'btn-nuit' => ! $statut, 'btn-ligne' => $statut])>Toutes <span class="opacity-60">{{ $tous }}</span></a>
        @foreach (\App\Enums\Statut::cases() as $s)
            <a href="{{ route('admin.precommandes.index', array_filter(['statut' => $s->value, 'q' => $q])) }}" @class(['btn py-2.5!', 'btn-nuit' => $statut === $s, 'btn-ligne' => $statut !== $s])>{{ $s->label() }} <span class="opacity-60">{{ $compteurs[$s->value] ?? 0 }}</span></a>
        @endforeach
    </nav>

    {{-- Recherche --}}
    <form method="get" class="mb-6 flex gap-2">
        @if ($statut)<input type="hidden" name="statut" value="{{ $statut->value }}">@endif
        <input type="search" name="q" value="{{ $q }}" placeholder="TEF-XXXXX, nom, téléphone, commune…" class="champ flex-1" aria-label="Rechercher">
        <button class="btn btn-nuit">Chercher</button>
    </form>

    {{-- Liste --}}
    <div class="grid gap-2">
        @forelse ($precommandes as $p)
            <article class="carte grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                <a href="{{ route('admin.precommandes.show', $p) }}" class="grid gap-3 sm:grid-cols-[150px_1fr_auto] sm:items-center sm:gap-6">
                    <div>
                        <p class="font-display text-2xl leading-none">{{ $p->reference }}</p>
                        <p class="mt-1 text-xs opacity-60">{{ $p->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate"><strong>{{ $p->nom }}</strong> <span class="opacity-60">· {{ $p->telephone }} · {{ $p->commune }}</span></p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($p->lignes as $l)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-nuit/5 py-0.5 pr-2.5 pl-0.5 text-xs font-semibold">
                                    @if ($l->produit)<img src="{{ \App\Models\Produit::urlImage($l->produit->image_face) }}" alt="" class="size-6 rounded-full bg-terre/30 object-contain">@endif
                                    {{ $l->libelle }} · {{ $l->taille }} ×{{ $l->quantite }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <p class="font-display text-2xl tabular-nums sm:text-right">{{ fcfa($p->total) }}</p>
                </a>
                {{-- changer le statut sans ouvrir la fiche --}}
                <form method="post" action="{{ route('admin.precommandes.statut', $p) }}" class="flex items-center gap-2 border-t-2 border-nuit/5 pt-3 sm:border-0 sm:pt-0">
                    @csrf @method('patch')
                    <label class="sr-only" for="statut-{{ $p->id }}">Statut de {{ $p->reference }}</label>
                    <select id="statut-{{ $p->id }}" name="statut" data-auto-submit class="h-10 cursor-pointer rounded-full border-2 border-nuit/15 bg-white pr-8 pl-3 text-xs font-bold tracking-[.1em] uppercase hover:border-nuit">
                        @foreach (\App\Enums\Statut::cases() as $s)
                            <option value="{{ $s->value }}" @selected($p->statut === $s)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @include('admin.partials.statut', ['statut' => $p->statut])
                    @if ($p->capture)
                        <a href="{{ route('admin.precommandes.show', $p) }}#capture" title="Capture de paiement reçue" class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-xl bg-[#1dc4f0] ring-2 ring-nuit/10"><img src="{{ route('admin.precommandes.capture', $p) }}" alt="Capture Wave" class="size-full object-cover"></a>
                    @endif
                </form>
            </article>
        @empty
            <div class="grid place-items-center gap-2 rounded-2xl border-2 border-dashed border-nuit/20 px-6 py-16 text-center">
                <p class="font-display text-3xl uppercase">Rien par ici</p>
                <p class="text-sm opacity-60">{{ $q || $statut ? 'Aucune précommande ne correspond à ce filtre.' : 'Les précommandes apparaîtront ici.' }}</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($precommandes->hasPages())
        <nav class="mt-8 flex items-center justify-between gap-4" aria-label="Pages">
            <a href="{{ $precommandes->previousPageUrl() ?? '#' }}" @class(['btn btn-ligne', 'pointer-events-none opacity-30' => $precommandes->onFirstPage()])>← Précédentes</a>
            <span class="text-sm font-semibold tabular-nums">Page {{ $precommandes->currentPage() }} / {{ $precommandes->lastPage() }}</span>
            <a href="{{ $precommandes->nextPageUrl() ?? '#' }}" @class(['btn btn-ligne', 'pointer-events-none opacity-30' => ! $precommandes->hasMorePages()])>Suivantes →</a>
        </nav>
    @endif
@endsection
