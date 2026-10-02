<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Tiakolisé et fière</title>
<!-- écran de chargement : une seule fois par visite, et seulement si JS est actif -->
<script>try{if(!sessionStorage.getItem("tk-intro"))document.documentElement.classList.add("intro-on")}catch{}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo:ital,wdth,wght@0,62..125,400..800;1,62..125,400..800&display=swap">
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<!-- ============ 0. CHARGEMENT — le message, puis le rideau monte ============ -->
<div class="intro" id="intro" aria-hidden="true">
  <p class="intro__txt"><span class="intro__w is-ocre" style="--i:0"><span>Monétisez</span></span> <span class="intro__w" style="--i:1"><span>les</span></span> <span class="intro__w" style="--i:2"><span>clips</span></span> <span class="intro__w is-red" style="--i:3"><span>afro</span></span> <span class="intro__w" style="--i:4"><span>francophones</span></span></p>
</div>

<!-- ============ 1. HERO — la fille qui porte le t-shirt ============ -->
<header class="hero" id="top">
  <div class="hero__top">
    <img class="hero__logo" src="/assets/logo-tiakolise.png" alt="tiakolisé et fière">
  </div>
  <h1>Tiakolisé et fière — Mélo Décalé</h1>
  <div class="hero__cols">
    <figure class="hero__col" style="margin:0"><video src="/assets/shoot-6574.mp4" poster="/assets/shoot-6574.jpg" autoplay muted loop playsinline preload="auto"></video><figcaption>Dos · Warning</figcaption></figure>
    <figure class="hero__col" style="margin:0"><video src="/assets/shoot-6571.mp4" poster="/assets/shoot-6571.jpg" autoplay muted loop playsinline preload="auto"></video><figcaption>Face · Mélo Décalé</figcaption></figure>
    <figure class="hero__col" style="margin:0"><video src="/assets/shoot-6572.mp4" poster="/assets/shoot-6572.jpg" autoplay muted loop playsinline preload="auto"></video><figcaption>Blanc · Rouge</figcaption></figure>
    <figure class="hero__col" style="margin:0"><video src="/assets/shoot-6573.mp4" poster="/assets/shoot-6573.jpg" autoplay muted loop playsinline preload="auto"></video><figcaption>Abidjan</figcaption></figure>
  </div>
  <div class="marquee" aria-label="Fait par nous et pour nous. Parce que notre voix compte. Monétisez les clips afro francophones.">
    <div class="marquee__track" aria-hidden="true" data-repeat>
      <span>Fait par nous et pour nous</span><span>Parce que notre voix compte</span><span>Monétisez les clips afro francophones</span>
    </div>
  </div>
</header>

<main>
<!-- ============ 2. LES CINTRES — défilement horizontal piloté par le scroll ============ -->
<section class="rack" id="collection" aria-label="La collection">
  <div class="rack__stage">
    <div class="rack__head">
      <p>Tiakolisé et fière</p>
      <p>Fait par nous, pour nous !</p>
    </div>
    <div class="rack__names" id="names" aria-hidden="true"></div>
    <div class="rack__slides" id="slides"></div>
    <div class="rack__dots" id="dots" aria-hidden="true"></div>
    <div class="rack__foot">
      <div class="rack__info">
        <span class="rack__count" id="count"></span>
        <span class="rack__detail" id="detail">Chargement de la collection…</span>
        <span class="rack__hint" id="hint">Passe la souris sur le t-shirt pour voir le dos</span>
      </div>
      <div class="rack__side">
        <span class="rack__stock" id="stock" hidden></span>
        <span class="rack__price" id="prix"></span>
        <div class="rack__sizes" id="sizes" role="radiogroup" aria-label="Taille"></div>
        <button class="cta" id="ajout" type="button">Ajouter au panier</button>
      </div>
    </div>
  </div>
</section>

<!-- Panier : toujours visible, ouvre le tiroir -->
<button type="button" id="panier-btn" data-ouvre-panier aria-haspopup="dialog" aria-controls="tiroir" class="fixed top-[calc(env(safe-area-inset-top,0px)+16px)] right-[var(--gutter)] z-50 inline-flex cursor-pointer items-center gap-2.5 rounded-full bg-creme py-2 pr-2 pl-5 text-xs font-bold tracking-[.14em] text-nuit uppercase shadow-[0_8px_24px_rgba(13,9,7,.25)] transition hover:-translate-y-0.5">
  Panier <span id="panier-nb" class="grid size-7 place-items-center rounded-full bg-rouge text-[13px] tracking-normal text-creme tabular-nums">0</span>
</button>
<p id="panier-live" class="sr-only" aria-live="polite"></p>

<!-- ============ 3. MUR DE TÉLÉS — images du clip Mélo Décalé ============ -->
<section class="tvroom" id="clip" aria-label="Mélo Décalé, le clip">
  <div class="tvroom__head">
    <img src="/assets/logo-melo.png" alt="Mélo Décalé">
    <p>Un titre, une culture, une économie. Chaque pièce porte le même message : les clips afro francophones méritent d'être payés à leur juste valeur.</p>
  </div>
  <div class="wall" id="wall" aria-hidden="true"></div>

  <div class="film" aria-label="Mélo Décalé. Fait par nous, pour nous. Parce que notre voix compte. Monétisez les clips afro francophones.">
    <div class="film__track" id="film" aria-hidden="true"></div>
  </div>
</section>

</main>

<footer class="foot">
  <img src="/assets/logo-melo-white.png" alt="Mélo Décalé">
  <p>Pièces uniques en série limitée, en soutien à Tiakola. Tiakolisé et fière × Mélo Décalé.</p>
  <button type="button" class="cta" data-ouvre-panier style="background:var(--color-rouge);border:0;cursor:pointer">Précommander</button>
</footer>

<!-- ============ 4. PANIER — tiroir latéral : 1 panier → 2 coordonnées → 3 paiement Wave (+ capture) ============ -->
<dialog id="tiroir" aria-labelledby="tiroir-titre" class="tiroir scheme-light fixed inset-y-0 right-0 left-auto m-0 h-dvh max-h-none w-full max-w-[480px] bg-creme p-0 text-nuit shadow-[-20px_0_60px_rgba(13,9,7,.35)] backdrop:bg-nuit/60 backdrop:backdrop-blur-[2px]">
  <div class="flex h-full flex-col">
    <header class="flex items-center justify-between gap-4 border-b-2 border-nuit px-5 pt-[calc(env(safe-area-inset-top,0px)+14px)] pb-3">
      <ol id="pc-nav" class="flex gap-3 text-[10px] font-bold tracking-[.12em] whitespace-nowrap uppercase min-[400px]:gap-4 min-[400px]:text-[11px] min-[400px]:tracking-[.18em]" aria-label="Étapes">
        <li data-nav="panier">1 Panier</li><li data-nav="infos">2 Coordonnées</li><li data-nav="paiement">3 Paiement</li>
      </ol>
      <button type="button" data-ferme class="-mr-2 grid size-11 shrink-0 place-items-center rounded-full transition hover:bg-nuit/5" aria-label="Fermer">
        <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </header>

    <!-- Étape 1 : panier + livraison -->
    <div data-etape="panier" class="flex min-h-0 flex-1 flex-col">
      <div class="min-h-0 flex-1 overflow-y-auto px-5 pt-7 pb-6">
        <h2 id="tiroir-titre" class="font-display text-[44px] leading-none uppercase">Ton panier</h2>
        <ul id="pc-panier" class="mt-6 grid"></ul>
        <div id="pc-livraison">
          <p class="mt-7 text-xs font-bold tracking-[.2em] uppercase">Livraison</p>
          <label class="mt-3 flex cursor-pointer gap-4 rounded-xl border-2 border-nuit bg-[#fffaf3] px-4 py-4">
            <input type="radio" name="livraison" value="yango" checked class="sr-only">
            <span class="mt-1 size-5 shrink-0 rounded-full bg-rouge ring-4 ring-rouge/20" aria-hidden="true"></span>
            <span>
              <span class="block text-lg leading-tight">Livraison Yango · Abidjan uniquement</span>
              <span class="mt-1.5 block text-[15px] leading-snug text-nuit/70">On t'envoie ta pièce par Yango, à l'adresse que tu nous donnes. La course n'est pas incluse : tu la paies directement au livreur à la réception, en espèces ou par Wave. Son prix dépend de ta commune.</span>
            </span>
          </label>
        </div>
      </div>
      <footer id="pc-pied" class="grid gap-3 border-t-2 border-nuit px-5 pt-4 pb-[calc(env(safe-area-inset-bottom,0px)+18px)]">
        <div class="flex items-baseline justify-between text-[17px]"><span>Sous-total</span><span data-total class="tabular-nums"></span></div>
        <div class="flex items-baseline justify-between gap-4 text-[17px]"><span>Livraison</span><span class="text-right">Course Yango payée au livreur</span></div>
        <div class="flex items-baseline justify-between border-t border-nuit/20 pt-2">
          <span class="font-display text-[32px] uppercase">Total</span>
          <span data-total class="font-display text-[32px] tabular-nums"></span>
        </div>
        <button type="button" data-vers="infos" class="h-14 w-full rounded-md bg-nuit text-sm font-bold tracking-[.22em] text-creme uppercase transition hover:bg-rouge">Valider la commande</button>
      </footer>
    </div>

    <!-- Étape 2 : coordonnées -->
    <form id="pc-form" data-etape="infos" hidden class="flex min-h-0 flex-1 flex-col" novalidate>
      <div class="grid min-h-0 flex-1 content-start gap-5 overflow-y-auto px-5 pt-7 pb-6">
        <h2 class="font-display text-[44px] leading-none uppercase">Tes coordonnées</h2>
        <label class="grid gap-2">
          <span class="text-xs font-bold tracking-[.18em] uppercase">Prénom et nom</span>
          <input name="nom" required minlength="2" maxlength="80" autocomplete="name" class="h-12 rounded-lg border-2 border-nuit/20 bg-[#fffaf3] px-4 text-lg focus:border-nuit focus:outline-none">
        </label>
        <div class="grid grid-cols-2 gap-4">
          <label class="grid gap-2">
            <span class="text-xs font-bold tracking-[.18em] uppercase">Téléphone WhatsApp</span>
            <input name="telephone" type="tel" required maxlength="30" autocomplete="tel" inputmode="tel" class="h-12 min-w-0 rounded-lg border-2 border-nuit/20 bg-[#fffaf3] px-4 text-lg focus:border-nuit focus:outline-none">
          </label>
          <label class="grid gap-2">
            <span class="text-xs font-bold tracking-[.18em] uppercase">E-mail (facultatif)</span>
            <input name="email" type="email" maxlength="120" autocomplete="email" class="h-12 min-w-0 rounded-lg border-2 border-nuit/20 bg-[#fffaf3] px-4 text-lg focus:border-nuit focus:outline-none">
          </label>
        </div>
        <label class="grid gap-2">
          <span class="text-xs font-bold tracking-[.18em] uppercase">Quartier et repère</span>
          <input name="quartier" required minlength="2" maxlength="120" autocomplete="address-line1" class="h-12 rounded-lg border-2 border-nuit/20 bg-[#fffaf3] px-4 text-lg focus:border-nuit focus:outline-none">
        </label>
        <label class="grid gap-2">
          <span class="text-xs font-bold tracking-[.18em] uppercase">Commune à Abidjan</span>
          <select name="commune" required class="h-13 rounded-lg border-2 border-nuit/20 bg-[#fffaf3] px-4 text-lg focus:border-nuit focus:outline-none">
            <option value="">Choisis ta commune</option>
          </select>
        </label>
        <label class="grid gap-2">
          <span class="text-xs font-bold tracking-[.18em] uppercase">Note (facultatif)</span>
          <textarea name="note_client" rows="3" maxlength="500" placeholder="Infos pour le livreur" class="rounded-lg border-2 border-nuit/20 bg-[#fffaf3] px-4 py-3 text-lg placeholder:text-nuit/45 focus:border-nuit focus:outline-none"></textarea>
        </label>

        <!-- pot de miel anti-spam : invisible pour les humains -->
        <input name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="absolute -left-[9999px] h-px w-px opacity-0">

        <p id="pc-erreur" role="alert" class="hidden rounded-lg bg-rouge/10 px-4 py-3 text-sm font-semibold text-rouge"></p>
      </div>
      <footer class="grid gap-3 border-t-2 border-nuit px-5 pt-4 pb-[calc(env(safe-area-inset-bottom,0px)+14px)]">
        <div class="flex items-baseline justify-between border-t border-nuit/20 pt-2">
          <span class="font-display text-[32px] uppercase">Total</span>
          <span data-total class="font-display text-[32px] tabular-nums"></span>
        </div>
        <button type="submit" class="h-14 w-full rounded-md bg-nuit text-sm font-bold tracking-[.22em] text-creme uppercase transition hover:bg-rouge disabled:cursor-wait disabled:opacity-50">Passer au paiement Wave</button>
        <button type="button" data-vers="panier" class="justify-self-start px-3 py-1 text-[17px] font-semibold underline underline-offset-4">← Retour au panier</button>
      </footer>
    </form>

    <!-- Étape 3 : paiement Wave + envoi de la capture -->
    <div data-etape="paiement" hidden class="flex min-h-0 flex-1 flex-col">
      <div class="min-h-0 flex-1 overflow-y-auto px-5 pt-7 pb-6">
        <h2 class="font-display text-[44px] leading-none uppercase">Paiement Wave</h2>
        <div class="mt-6 rounded-xl border-2 border-nuit/15 bg-[#fffaf3] px-6 py-5">
          <p class="text-xs font-bold tracking-[.2em] uppercase">Commande <span data-ref></span></p>
          <div id="pc-recap" class="mt-2 grid gap-1 text-[17px]"></div>
        </div>
        <div class="mt-5 rounded-xl bg-[#1dc4f0] px-6 py-6">
          <p class="text-xs font-bold tracking-[.2em] uppercase">Montant à envoyer</p>
          <p data-montant class="mt-2 font-display text-[56px] leading-none tabular-nums"></p>
          <p class="mt-5 text-xs font-bold tracking-[.2em] uppercase">Numéro Wave</p>
          <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-2">
            <span data-wave class="text-[28px] font-bold tabular-nums"></span>
            <button type="button" id="pc-copier" class="rounded-full border-2 border-nuit px-5 py-1.5 text-xs font-bold tracking-[.18em] uppercase transition hover:bg-nuit hover:text-[#1dc4f0]">Copier</button>
          </div>
          <a id="pc-lien-wave" hidden target="_blank" rel="noopener" class="mt-5 flex h-12 items-center justify-center rounded-md bg-nuit text-xs font-bold tracking-[.2em] text-creme uppercase">Payer avec l'application Wave ↗</a>
        </div>
        <ol class="mt-6 grid list-decimal gap-3 pl-6 text-[19px] leading-snug">
          <li>Ouvre ton application <strong>Wave</strong> et envoie <strong data-montant></strong> au <strong data-wave></strong>.</li>
          <li>Fais une <strong>capture d'écran</strong> de la confirmation de paiement.</li>
          <li>Envoie ta capture avec le <strong>bouton vert</strong> ci-dessous : elle arrive directement chez nous avec ton numéro de commande <strong data-ref></strong>.</li>
        </ol>
        <div id="pc-apercu" hidden class="mt-5 flex items-center gap-4 rounded-xl border-2 border-nuit/15 bg-[#fffaf3] p-3">
          <img alt="" class="size-16 rounded-md bg-nuit/5 object-cover">
          <p class="text-sm font-semibold" data-etat>Envoi de ta capture…</p>
        </div>
        <p id="pc-erreur-capture" role="alert" class="mt-4 hidden rounded-lg bg-rouge/10 px-4 py-3 text-sm font-semibold text-rouge"></p>
        <p class="mt-6 text-[15px] leading-snug text-nuit/70">Ta commande est confirmée dès qu'on reçoit ta capture. On te répond sur le <span data-tel></span> pour caler la livraison. Le montant Wave couvre uniquement tes pièces : la course Yango se paie au livreur à la réception.</p>
      </div>
      <footer class="grid gap-3 border-t-2 border-nuit px-5 pt-4 pb-[calc(env(safe-area-inset-bottom,0px)+14px)]">
        <label id="pc-envoyer" class="flex h-14 w-full cursor-pointer items-center justify-center rounded-md bg-[#25d366] px-3 text-center text-[13px] font-bold tracking-[.16em] whitespace-nowrap text-nuit uppercase transition hover:brightness-95 has-[:disabled]:cursor-wait has-[:disabled]:opacity-60 min-[400px]:text-sm min-[400px]:tracking-[.22em]">
          Envoyer ma capture Wave
          <input id="pc-capture" type="file" accept="image/*" class="sr-only">
        </label>
        <div class="flex flex-wrap items-center justify-between gap-2">
          <button type="button" data-vers="infos" class="px-3 py-1 text-[17px] font-semibold underline underline-offset-4">← Modifier mes coordonnées</button>
          <a id="pc-whatsapp" target="_blank" rel="noopener" class="px-3 py-1 text-sm font-semibold text-nuit/70 underline underline-offset-4">ou par WhatsApp</a>
        </div>
      </footer>
    </div>

    <!-- Étape 4 : merci -->
    <div data-etape="merci" hidden class="flex flex-1 flex-col justify-end gap-5 bg-nuit px-6 pt-10 pb-[calc(env(safe-area-inset-bottom,0px)+28px)] text-creme" tabindex="-1">
      <p class="font-display text-[64px] leading-[.9] uppercase">Merci,<br>c'est reçu.</p>
      <p class="text-lg opacity-80">On vérifie ton paiement Wave et on te confirme ta commande <strong data-ref class="font-display text-2xl tracking-wide text-ocre"></strong> sur WhatsApp au <span data-tel></span> pour caler la livraison.</p>
      <button type="button" data-ferme class="h-14 rounded-md bg-creme text-sm font-bold tracking-[.22em] text-nuit uppercase transition hover:bg-ocre">Continuer à regarder</button>
    </div>
  </div>
</dialog>



</body></html>