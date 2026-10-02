@extends('admin.layout', ['titre' => 'Tableau de bord'])

@section('contenu')
    @include('admin.partials.titre', ['surtitre' => now()->locale('fr')->isoFormat('dddd D MMMM'), 'titre' => 'Salut '.auth()->user()->name])

    {{-- Les chiffres --}}
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.precommandes.index', ['statut' => 'a_verifier']) }}" class="carte group bg-ocre! transition hover:-translate-y-0.5">
            <p class="surtitre opacity-80!">Paiements à vérifier</p>
            <p class="mt-3 font-display text-6xl leading-none tabular-nums">{{ $aVerifier }}</p>
            <p class="mt-2 text-sm font-semibold">capture{{ $aVerifier > 1 ? 's' : '' }} Wave reçue{{ $aVerifier > 1 ? 's' : '' }} →</p>
        </a>
        <a href="{{ route('admin.precommandes.index') }}" class="carte transition hover:-translate-y-0.5">
            <p class="surtitre">Commandes</p>
            <p class="mt-3 font-display text-6xl leading-none tabular-nums">{{ $nombre }}</p>
            <p class="mt-2 text-sm opacity-60">dont {{ $enAttente }} en attente de paiement</p>
        </a>
        <div class="carte bg-nuit! text-creme">
            <p class="surtitre">Chiffre d'affaires prévu</p>
            <p class="mt-3 font-display text-5xl leading-none whitespace-nowrap tabular-nums xl:text-6xl">{{ number_format($prevu, 0, ',', ' ') }}<span class="ml-1 text-xl text-creme/60">FCFA</span></p>
        </div>
        @php $total = $reservees + $restant; $pct = $total ? round($reservees / $total * 100) : 0; @endphp
        <div class="carte">
            <p class="surtitre">Série limitée</p>
            <p class="mt-3 font-display text-6xl leading-none tabular-nums">{{ $pct }}<span class="text-3xl">%</span></p>
            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-nuit/10"><div class="h-full rounded-full bg-rouge" style="width: {{ $pct }}%"></div></div>
            <p class="mt-2 text-sm opacity-60">{{ $reservees }} réservées · {{ $restant }} restantes</p>
        </div>
    </section>

    <div class="mt-10 grid gap-10 xl:grid-cols-[1.4fr_1fr]">
        {{-- Dernières précommandes --}}
        <section>
            <div class="mb-4 flex items-baseline justify-between">
                <h2 class="font-display text-3xl uppercase">Dernières commandes</h2>
                <a href="{{ route('admin.precommandes.index') }}" class="text-xs font-bold tracking-[.14em] uppercase underline underline-offset-4">Tout voir</a>
            </div>
            <div class="grid gap-2">
                @forelse ($dernieres as $p)
                    <a href="{{ route('admin.precommandes.show', $p) }}" class="carte flex items-center gap-4 py-4! transition hover:bg-nuit/[.03]">
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-2"><span class="font-display text-xl">{{ $p->reference }}</span> @include('admin.partials.statut', ['statut' => $p->statut])</p>
                            <p class="mt-1 truncate text-sm"><strong>{{ $p->nom }}</strong> · {{ $p->commune }}</p>
                            <p class="truncate text-xs opacity-60">{{ $p->lignes->map(fn ($l) => "{$l->libelle} {$l->taille} ×{$l->quantite}")->implode(', ') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-display text-xl tabular-nums">{{ fcfa($p->total) }}</p>
                            <p class="text-xs opacity-60">{{ $p->created_at->locale('fr')->diffForHumans() }}</p>
                        </div>
                    </a>
                @empty
                    <div class="grid place-items-center gap-2 rounded-2xl border-2 border-dashed border-nuit/20 px-6 py-14 text-center">
                        <p class="font-display text-3xl uppercase">Pas encore de commande</p>
                        <p class="text-sm opacity-60">Elles apparaîtront ici dès qu'un client valide son panier.</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Stock --}}
        <section>
            <div class="mb-4 flex items-baseline justify-between">
                <h2 class="font-display text-3xl uppercase">Stock</h2>
                <a href="{{ route('admin.pieces.index') }}" class="text-xs font-bold tracking-[.14em] uppercase underline underline-offset-4">Gérer</a>
            </div>
            <div class="grid gap-2">
                @foreach ($pieces as $piece)
                    @php $reste = $piece->stocks->sum('quantite'); @endphp
                    <a href="{{ route('admin.pieces.edit', $piece) }}" @class(['carte flex items-center gap-4 py-3! transition hover:bg-nuit/[.03]', 'opacity-50' => ! $piece->actif])>
                        <img src="{{ \App\Models\Produit::urlImage($piece->image_face) }}" alt="" class="papier size-16 shrink-0 rounded-xl object-contain p-1">
                        <div class="min-w-0 flex-1">
                            <p class="font-display text-lg leading-tight uppercase">{{ $piece->nom }} <span class="text-terre">{{ $piece->couleur }}</span></p>
                            <div class="mt-1.5">@include('admin.partials.stock')</div>
                        </div>
                        <p @class(['font-display text-2xl tabular-nums', 'text-rouge' => $reste <= 10])>{{ $reste ?: 'Épuisé' }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
@endsection
