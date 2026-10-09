{{-- ============ GRILLE BENTO — remplace le mur de télés ============
     Photos et vidéos : back office « Photos du site » ; pièces, prix et stock : remplis par app.js depuis /api/catalogue. --}}
@php
    $video1 = $medias['tv_video'][0] ?? null;
    $video2 = $medias['tv_video'][1] ?? null;
    $images = $medias['tv_image'] ?? collect();
    $portrait = $medias['hero'][1] ?? null; // photo de la case « comment ça marche »
@endphp
<div class="bento" id="bento">
  {{-- la pièce, qui change toute seule --}}
  <a href="#collection" class="bento__case bento__produit" id="bento-produit" data-voir="0" aria-label="Voir la pièce">
    <img id="bento-produit-img" src="" alt="">
    <span class="bento__pastille bento__pastille--clair">Série limitée</span>
    <span class="bento__bas">
      <span class="bento__titre" id="bento-produit-nom">La collection</span>
      <span class="bento__prix" id="bento-produit-prix"></span>
    </span>
  </a>

  {{-- le shooting --}}
  <figure class="bento__case bento__video">
    @if ($video1)
      <video src="{{ $video1->url }}" @if ($video1->poster) poster="{{ $video1->poster_url }}" @endif autoplay muted loop playsinline preload="metadata"></video>
    @endif
    <figcaption class="bento__pastille"><i class="bento__rec"></i> Le shooting</figcaption>
  </figure>

  {{-- les images du clip, qui zappent --}}
  <figure class="bento__case bento__zap">
    <img id="bento-zap" src="{{ $images->first()?->url }}" alt="">
    <figcaption class="bento__pastille">Mélo Décalé</figcaption>
  </figure>

  {{-- la collection, comme une carte de menu --}}
  <div class="bento__case bento__collection">
    <p class="bento__h">La collection</p>
    <ul class="bento__liste" id="bento-liste"></ul>
  </div>

  {{-- série limitée : le vrai stock --}}
  <div class="bento__case bento__stats">
    <p class="bento__filigrane" aria-hidden="true">Série<br>limitée</p>
    <p class="bento__h">Série limitée</p>
    <p class="bento__chiffre" id="bento-restant">—</p>
    <p class="bento__sous" id="bento-restant-texte">pièces encore disponibles</p>
    <div class="bento__barres" id="bento-barres"></div>
  </div>

  {{-- comment ça marche --}}
  <div class="bento__case bento__etapes">
    @if ($portrait)
      @if ($portrait->est_video)
        <video src="{{ $portrait->url }}" @if ($portrait->poster) poster="{{ $portrait->poster_url }}" @endif autoplay muted loop playsinline preload="metadata"></video>
      @else
        <img src="{{ $portrait->url }}" alt="">
      @endif
    @endif
    <ul class="bento__chips">
      <li>Choisis ta pièce</li>
      <li>Paie avec Wave ou Orange Money</li>
      <li>Livré par Yango</li>
    </ul>
  </div>

  {{-- livraison --}}
  <div class="bento__case bento__livraison">
    <p class="bento__h">Livraison Yango</p>
    <p class="bento__sous">Partout à Abidjan, course payée au livreur</p>
    <ul class="bento__communes">
      @foreach (\App\Models\Precommande::COMMUNES as $commune)<li>{{ $commune }}</li>@endforeach
    </ul>
  </div>

  {{-- le manifeste --}}
  <div class="bento__case bento__manifeste">
    <p class="bento__manif"><span class="text-rouge">Monétisez</span> les clips <span class="text-creme">afro</span> francophones</p>
    <p class="bento__sous">Fait par nous, pour nous</p>
  </div>

  {{-- Mélo Décalé, en grand --}}
  <figure class="bento__case bento__clip">
    @if ($video2)
      <video src="{{ $video2->url }}" @if ($video2->poster) poster="{{ $video2->poster_url }}" @endif autoplay muted loop playsinline preload="metadata"></video>
    @endif
    <figcaption class="bento__bas">
      <span class="bento__titre">Mélo Décalé</span>
      <span class="bento__prix">Parce que notre voix compte</span>
    </figcaption>
  </figure>
</div>
