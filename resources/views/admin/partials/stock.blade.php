{{-- Stock par taille d'une pièce : une pastille par taille, rouge sous 5, barrée à 0 --}}
<div class="flex flex-wrap gap-1.5">
    @foreach ($piece->stocks as $s)
        <span @class([
            'inline-flex items-baseline gap-1 rounded-full px-2.5 py-1 text-xs font-bold tabular-nums',
            'bg-rouge/10 text-rouge line-through' => $s->quantite === 0,
            'bg-rouge text-creme' => $s->quantite > 0 && $s->quantite <= 5,
            'bg-nuit/5' => $s->quantite > 5,
        ])>{{ $s->taille }} <span class="font-semibold opacity-70">{{ $s->quantite }}</span></span>
    @endforeach
</div>
