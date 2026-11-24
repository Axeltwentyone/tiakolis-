{{-- Tableau de bord : paiements en ligne (Jeko) et répartition par moyen --}}
@php
    $couleurs = ['Orange Money' => '#ff7900', 'Wave' => '#1dc4f0', 'MTN MoMo' => '#ffcc00', 'Moov Money' => '#0a4ea0', 'Djamo' => '#c4743c', 'Capture (lien)' => '#0d0907'];
    $max = max(1, $parMoyen->max('t') ?? 1);
@endphp
<section class="mt-10 grid gap-3 lg:grid-cols-[1fr_1.6fr]">
    <div class="carte bg-nuit! text-creme">
        <p class="surtitre">Encaissé en ligne · Jeko</p>
        <p class="mt-3 font-display text-5xl leading-none whitespace-nowrap tabular-nums xl:text-6xl">{{ number_format($enLigne, 0, ',', ' ') }}<span class="ml-1 text-xl text-creme/60">FCFA</span></p>
        <p class="mt-2 text-sm text-ocre">{{ $nbEnLigne }} précommande{{ $nbEnLigne > 1 ? 's' : '' }} payée{{ $nbEnLigne > 1 ? 's' : '' }} et validée{{ $nbEnLigne > 1 ? 's' : '' }} automatiquement</p>
        <p class="mt-4 text-xs opacity-60">Détail des transactions, frais et virements : dans le tableau de bord Jeko.</p>
    </div>
    <div class="carte">
        <div class="flex items-baseline justify-between gap-3">
            <p class="surtitre">Par moyen de paiement</p>
            <p class="text-xs opacity-60">précommandes payées ou à vérifier</p>
        </div>
        @if ($parMoyen->isEmpty())
            <p class="mt-6 text-sm opacity-60">Pas encore de paiement.</p>
        @else
            <div class="mt-4 grid gap-3">
                @foreach ($parMoyen as $m)
                    <div class="grid grid-cols-[110px_1fr_auto] items-center gap-3 text-sm sm:grid-cols-[130px_1fr_auto]">
                        <span class="truncate font-semibold">{{ $m['moyen'] }}</span>
                        <span class="h-3 overflow-hidden rounded-full bg-nuit/5"><span class="block h-full rounded-full" style="width: {{ max(3, round($m['t'] / $max * 100)) }}%; background: {{ $couleurs[$m['moyen']] ?? '#c4743c' }}"></span></span>
                        <span class="text-right tabular-nums whitespace-nowrap"><strong>{{ fcfa($m['t']) }}</strong> <span class="opacity-60">· {{ $m['n'] }}</span></span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
