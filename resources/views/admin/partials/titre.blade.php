{{-- Titre de page : surtitre + grand titre Anton + actions à droite --}}
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        @isset($surtitre)<p class="surtitre">{{ $surtitre }}</p>@endisset
        <h1 class="mt-1 font-display text-5xl leading-[.9] uppercase sm:text-6xl">{{ $titre }}</h1>
    </div>
    @isset($actions)<div class="flex flex-wrap gap-2">{!! $actions !!}</div>@endisset
</div>
