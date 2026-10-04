{{-- Aperçu d'un média (photo ou vidéo) --}}
@if ($media->est_video)
    <video src="{{ $media->url }}" @if ($media->poster) poster="{{ $media->poster_url }}" @endif autoplay muted loop playsinline preload="metadata"></video>
@else
    <img src="{{ $media->url }}" alt="" loading="lazy">
@endif
