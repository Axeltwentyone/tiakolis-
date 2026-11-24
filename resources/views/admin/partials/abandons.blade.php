{{-- Tableau de bord : clients arrivés au paiement en ligne sans le terminer (aucune précommande créée) --}}
<section class="mt-10">
    <div class="mb-4">
        <h2 class="font-display text-3xl uppercase">Paniers non payés <span class="text-terre">{{ $nbAbandons }}</span></h2>
        <p class="text-sm opacity-60">Clients arrivés au paiement sans le terminer (ces 14 derniers jours). Aucune précommande n'est créée et aucune pièce n'est réservée : un petit message WhatsApp peut les aider à finaliser.</p>
    </div>
    @if ($abandons->isEmpty())
        <div class="grid place-items-center gap-1 rounded-2xl border-2 border-dashed border-nuit/20 px-6 py-10 text-center">
            <p class="font-display text-2xl uppercase">Aucun panier en suspens</p>
            <p class="text-sm opacity-60">Tous les clients arrivés au paiement l'ont terminé.</p>
        </div>
    @else
        <div class="grid gap-2">
            @foreach ($abandons as $a)
                <article class="carte grid gap-3 py-4! sm:grid-cols-[1fr_auto] sm:items-center">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2">
                                <strong class="truncate">{{ $a->donnees['nom'] ?? '—' }}</strong>
                                <span class="rounded-full bg-nuit/10 px-2.5 py-1 text-[11px] font-bold tracking-[.1em] uppercase">{{ $a->created_at->locale('fr')->diffForHumans(['parts' => 1]) }}</span>
                                @if ($a->statut === 'echec')<span class="rounded-full bg-rouge/10 px-2.5 py-1 text-[11px] font-bold tracking-[.1em] text-rouge uppercase">Paiement refusé</span>@endif
                            </p>
                            <p class="mt-1 truncate text-sm">{{ $a->donnees['telephone'] ?? '' }} · {{ $a->donnees['email'] ?? '' }} · {{ $a->donnees['commune'] ?? '' }}</p>
                            <p class="truncate text-xs opacity-60">{{ $a->resume }} · tenté avec {{ \App\Support\Jeko::MOYENS[$a->moyen] ?? $a->moyen }}</p>
                        </div>
                        <p class="font-display text-xl whitespace-nowrap tabular-nums">{{ fcfa($a->montant) }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:justify-end">
                        <a href="{{ $a->whatsapp_relance }}" target="_blank" rel="noopener" class="btn bg-[#25d366] text-nuit">WhatsApp</a>
                        @if (! empty($a->donnees['email']))
                            <a href="mailto:{{ $a->donnees['email'] }}?subject={{ rawurlencode('Ta précommande Tiakolisé et fière') }}" class="btn btn-ligne">✉ E-mail</a>
                        @endif
                        <form method="post" action="{{ route('admin.paiements.ignorer', $a) }}">@csrf<button class="btn btn-ligne opacity-70">Ignorer</button></form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
