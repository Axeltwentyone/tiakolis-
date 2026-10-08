@extends('admin.layout', ['titre' => $p->reference])

@section('contenu')
    <a href="{{ route('admin.precommandes.index') }}" class="mb-4 inline-block text-xs font-bold tracking-[.14em] uppercase underline underline-offset-4 opacity-70 hover:opacity-100">← Commandes</a>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="surtitre">Reçue le {{ $p->created_at->locale('fr')->isoFormat('D MMMM YYYY [à] HH:mm') }}</p>
            <h1 class="mt-1 font-display text-6xl leading-[.9] sm:text-7xl">{{ $p->reference }}</h1>
        </div>
        @include('admin.partials.statut', ['statut' => $p->statut])
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <div class="grid content-start gap-6">
            {{-- Paiement Wave : la capture envoyée par le client --}}
            <section id="capture" class="carte">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="font-display text-2xl uppercase">Paiement Wave</h2>
                    <p class="font-display text-3xl tabular-nums">{{ fcfa($p->total) }}</p>
                </div>
                @if ($p->capture)
                    <p class="mt-1 text-sm opacity-60">Capture reçue {{ $p->capture_le?->locale('fr')->isoFormat('[le] D MMMM [à] HH:mm') }}. Vérifie dans Wave que tu as bien reçu {{ fcfa($p->total) }}.</p>
                    <a href="{{ route('admin.precommandes.capture', $p) }}" target="_blank" class="group mt-4 block overflow-hidden rounded-2xl bg-[#1dc4f0] p-3">
                        <img src="{{ route('admin.precommandes.capture', $p) }}" alt="Capture du paiement Wave de {{ $p->nom }}" class="mx-auto max-h-[70vh] w-auto rounded-xl bg-white shadow-lg">
                        <span class="mt-3 block text-center text-xs font-bold tracking-[.14em] text-nuit uppercase group-hover:underline">Ouvrir en grand ↗</span>
                    </a>
                    @if ($p->statut === \App\Enums\Statut::AVerifier)
                        <form method="post" action="{{ route('admin.precommandes.statut', $p) }}" class="mt-4 grid gap-2 sm:grid-cols-2">
                            @csrf @method('patch')
                            <button name="statut" value="payee" class="btn btn-nuit py-4!">✓ Paiement reçu</button>
                            <button name="statut" value="en_attente" class="btn btn-ligne py-4!">Pas reçu, attendre</button>
                        </form>
                    @endif
                @else
                    <div class="mt-4 rounded-2xl border-2 border-dashed border-nuit/20 px-5 py-8 text-center">
                        <p class="font-semibold">Pas encore de capture</p>
                        <p class="mt-1 text-sm opacity-60">Le client doit envoyer {{ fcfa($p->total) }} sur Wave puis sa capture. Elle apparaîtra ici et tu recevras un e-mail.</p>
                        @if ($p->modifiable())
                            <p class="mt-3 text-xs opacity-60">{{ $p->relances ? 'Relancé '.$p->relances.' fois, la dernière '.$p->relance_le?->locale('fr')->diffForHumans() : 'Jamais relancé' }}</p>
                            <div class="mt-3 flex flex-wrap justify-center gap-2">
                                <a href="{{ $p->whatsapp_relance }}" target="_blank" rel="noopener" class="btn bg-[#25d366] text-nuit">Relancer sur WhatsApp</a>
                                @if ($p->email)
                                    <form method="post" action="{{ route('admin.precommandes.relancer', $p) }}">@csrf<button class="btn btn-nuit">✉ Relancer par e-mail</button></form>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </section>

            {{-- Statut --}}
            <section class="carte">
                <h2 class="font-display text-2xl uppercase">Statut</h2>
                <form method="post" action="{{ route('admin.precommandes.statut', $p) }}" class="mt-4 flex flex-wrap gap-2">
                    @csrf @method('patch')
                    @foreach (\App\Enums\Statut::cases() as $s)
                        <button name="statut" value="{{ $s->value }}" @disabled($p->statut === $s)
                            @if ($s === \App\Enums\Statut::Annulee) data-confirme="Annuler {{ $p->reference }} ? Les pièces seront remises en stock." @endif
                            @class(['btn py-3!', 'btn-nuit cursor-default! ring-4 ring-ocre' => $p->statut === $s, 'btn-ligne' => $p->statut !== $s, 'text-rouge' => $s === \App\Enums\Statut::Annulee && $p->statut !== $s])>{{ $s->label() }}</button>
                    @endforeach
                </form>
                <p class="mt-3 text-sm opacity-60">« Annulée » remet les pièces en stock. Repasser une commande annulée dans un autre statut les reprend.</p>
            </section>

            {{-- Articles --}}
            <section class="carte">
                <h2 class="font-display text-2xl uppercase">Articles</h2>
                <ul class="mt-4 grid gap-3">
                    @foreach ($p->lignes as $l)
                        <li class="flex items-center gap-4">
                            @if ($l->produit)<img src="{{ \App\Models\Produit::urlImage($l->produit->image_face) }}" alt="" class="papier size-20 shrink-0 rounded-xl object-contain p-1">@endif
                            <div class="min-w-0 flex-1">
                                <p class="font-display text-xl leading-none uppercase">{{ $l->libelle }}</p>
                                <p class="mt-1 text-xs font-semibold tracking-[.14em] uppercase opacity-60">Taille {{ $l->taille }} · {{ $l->quantite }} × {{ fcfa($l->prix_unitaire) }}</p>
                            </div>
                            <p class="font-display text-xl tabular-nums">{{ fcfa($l->sous_total) }}</p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-5 grid gap-1 border-t-2 border-nuit pt-4">
                    <div class="flex items-baseline justify-between text-sm"><span>Livraison</span><span>Course Yango payée au livreur</span></div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs font-bold tracking-[.14em] uppercase">Total Wave</span>
                        <span class="font-display text-4xl tabular-nums">{{ fcfa($p->total) }}</span>
                    </div>
                </div>
            </section>

            {{-- Note interne --}}
            <section class="carte">
                <form method="post" action="{{ route('admin.precommandes.update', $p) }}">
                    @csrf @method('patch')
                    @foreach (['nom', 'telephone', 'email', 'commune', 'quartier'] as $champ)<input type="hidden" name="{{ $champ }}" value="{{ $p->{$champ} }}">@endforeach
                    <label for="note" class="font-display text-2xl uppercase">Note interne</label>
                    <p class="text-sm opacity-60">Visible seulement ici : livreur, échange, remarque…</p>
                    <textarea id="note" name="note" rows="4" class="champ mt-3">{{ old('note', $p->note) }}</textarea>
                    <button class="btn btn-nuit mt-3">Enregistrer la note</button>
                </form>
            </section>
        </div>

        {{-- Client et livraison --}}
        <aside class="grid content-start gap-6">
            <section class="carte bg-nuit! text-creme">
                <p class="surtitre">Client</p>
                <p class="mt-2 font-display text-4xl leading-none uppercase">{{ $p->nom }}</p>
                <p class="mt-3 text-creme/80"><span class="text-ocre">Livraison Yango</span><br>{{ $p->adresse }}</p>
                @if ($p->note_client)
                    <p class="mt-3 rounded-xl bg-creme/10 px-4 py-3 text-sm"><span class="surtitre block opacity-100! text-ocre">Pour le livreur</span>{{ $p->note_client }}</p>
                @endif
                <div class="mt-6 grid gap-2">
                    <a href="https://wa.me/{{ $p->whatsapp }}?text={{ rawurlencode("Bonjour {$p->nom}, c'est Tiakolisé et fière à propos de ta commande {$p->reference}.") }}" target="_blank" rel="noopener" class="btn bg-[#25d366] text-nuit">WhatsApp · {{ $p->telephone }}</a>
                    <a href="tel:{{ $p->telephone }}" class="btn border-2 border-creme/20 hover:border-creme">Appeler</a>
                    @if ($p->email)
                        <a href="mailto:{{ $p->email }}?subject={{ rawurlencode('Ta commande '.$p->reference) }}" class="btn bg-creme text-nuit hover:bg-ocre!">✉ {{ $p->email }}</a>
                    @endif
                </div>
            </section>

            <details class="carte group" @if ($errors->any()) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between font-display text-2xl uppercase">Modifier le contact <span class="transition group-open:rotate-45">+</span></summary>
                <form method="post" action="{{ route('admin.precommandes.update', $p) }}" class="mt-4 grid gap-4">
                    @csrf @method('patch')
                    <input type="hidden" name="note" value="{{ $p->note }}">
                    <label><span class="etiquette">Prénom et nom</span><input name="nom" value="{{ old('nom', $p->nom) }}" required class="champ"></label>
                    <label><span class="etiquette">Téléphone WhatsApp</span><input name="telephone" type="tel" value="{{ old('telephone', $p->telephone) }}" required class="champ"></label>
                    <label><span class="etiquette">E-mail</span><input name="email" type="email" value="{{ old('email', $p->email) }}" required class="champ"></label>
                    <label><span class="etiquette">Quartier et repère</span><input name="quartier" value="{{ old('quartier', $p->quartier) }}" required class="champ"></label>
                    <label><span class="etiquette">Commune</span>
                        <select name="commune" required class="champ">
                            @foreach (\App\Models\Precommande::COMMUNES as $c)<option @selected(old('commune', $p->commune) === $c)>{{ $c }}</option>@endforeach
                        </select>
                    </label>
                    @if ($errors->any())<p class="rounded-xl bg-rouge/10 px-4 py-3 text-sm font-semibold text-rouge">{{ $errors->first() }}</p>@endif
                    <button class="btn btn-nuit">Enregistrer</button>
                </form>
            </details>

            <form method="post" action="{{ route('admin.precommandes.destroy', $p) }}" class="text-center">
                @csrf @method('delete')
                <button data-confirme="Supprimer définitivement {{ $p->reference }} ? Les pièces seront remises en stock." class="text-xs font-bold tracking-[.14em] uppercase text-rouge underline underline-offset-4">Supprimer cette commande</button>
            </form>
        </aside>
    </div>
@endsection
