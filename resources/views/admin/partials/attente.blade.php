{{-- Tableau de bord : clients en attente de paiement, à relancer --}}
<section class="mt-10">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-display text-3xl uppercase">En attente de paiement <span class="text-terre">{{ $nbAttente }}</span></h2>
            <p class="text-sm opacity-60">Commandes passées sans capture de paiement. Les pièces restent réservées : relance les clients, ou annule la commande pour remettre les pièces en vente.</p>
        </div>
        @if ($nbAttente)
            <form method="post" action="{{ route('admin.precommandes.relancer-tous') }}">
                @csrf
                <button @disabled(! $aRelancerMail) data-confirme="Envoyer un e-mail de relance à {{ $aRelancerMail }} client(s) ? (Ceux relancés il y a moins de 24 h sont ignorés.)" class="btn btn-rouge disabled:cursor-not-allowed disabled:opacity-40">✉ Relancer tout le monde par e-mail ({{ $aRelancerMail }})</button>
            </form>
        @endif
    </div>

    @if ($attente->isEmpty())
        <div class="grid place-items-center gap-1 rounded-2xl border-2 border-dashed border-nuit/20 px-6 py-10 text-center">
            <p class="font-display text-2xl uppercase">Personne à relancer</p>
            <p class="text-sm opacity-60">Toutes les commandes ont envoyé leur capture de paiement.</p>
        </div>
    @else
        <div class="grid gap-2">
            @foreach ($attente as $p)
                <article class="carte grid gap-3 py-4! sm:grid-cols-[1fr_auto] sm:items-center">
                    <a href="{{ route('admin.precommandes.show', $p) }}" class="flex min-w-0 items-center gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2">
                                <span class="font-display text-xl">{{ $p->reference }}</span>
                                <span @class(['rounded-full px-2.5 py-1 text-[11px] font-bold tracking-[.1em] uppercase', 'bg-rouge text-creme' => $p->created_at->lt(now()->subDay()), 'bg-nuit/10' => ! $p->created_at->lt(now()->subDay())])>{{ $p->created_at->locale('fr')->diffForHumans(['parts' => 1]) }}</span>
                            </p>
                            <p class="mt-1 truncate text-sm"><strong>{{ $p->nom }}</strong> · {{ $p->telephone }} · {{ $p->commune }}</p>
                            <p class="text-xs opacity-60">
                                {{ $p->relances ? 'Relancé '.$p->relances.' fois, la dernière '.$p->relance_le?->locale('fr')->diffForHumans() : 'Jamais relancé' }}
                                @unless ($p->email) · pas d'e-mail @endunless
                            </p>
                        </div>
                        <p class="font-display text-xl whitespace-nowrap tabular-nums">{{ fcfa($p->total) }}</p>
                    </a>
                    <div class="flex flex-wrap gap-2 sm:justify-end">
                        <a href="{{ $p->whatsapp_relance }}" target="_blank" rel="noopener" class="btn bg-[#25d366] text-nuit">WhatsApp</a>
                        @if ($p->email)
                            <form method="post" action="{{ route('admin.precommandes.relancer', $p) }}">
                                @csrf
                                <button class="btn btn-nuit">✉ Relancer</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        @if ($nbAttente > $attente->count())
            <a href="{{ route('admin.precommandes.index', ['statut' => 'en_attente']) }}" class="mt-3 inline-block text-xs font-bold tracking-[.14em] uppercase underline underline-offset-4">Voir les {{ $nbAttente }} commandes en attente</a>
        @endif
    @endif
</section>
