{{-- Gabarit des e-mails aux couleurs du site : bandeau nuit + logo, fond crème, titres façon Anton.
     Tout est en styles en ligne et en tableaux : c'est ce que Gmail, Outlook et Apple Mail lisent le mieux. --}}
@php
    $nuit = '#0d0907'; $creme = '#f6efe4'; $rouge = '#e3342a'; $ocre = '#f2a33a';
    $logo = isset($message) ? $message->embed(public_path('assets/logo-tiakolise.png')) : asset('assets/logo-tiakolise.png');
@endphp
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>{{ $titre ?? 'Tiakolisé et fière' }}</title>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  body{margin:0;padding:0;background:{{ $creme }}}
  .display{font-family:Anton,Impact,'Arial Narrow Bold','Helvetica Neue',Arial,sans-serif;font-weight:400;text-transform:uppercase;letter-spacing:.01em}
  @media (max-width:600px){ .px{padding-left:22px!important;padding-right:22px!important} .grand{font-size:44px!important} }
</style>
</head>
<body style="margin:0;padding:0;background:{{ $creme }};">
{{-- texte d'aperçu dans la boîte de réception --}}
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $apercu ?? '' }}&#8204;&nbsp;&#8204;&nbsp;&#8204;&nbsp;&#8204;&nbsp;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $creme }};">
<tr><td align="center" style="padding:24px 12px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:{{ $creme }};">
    {{-- bandeau --}}
    <tr><td align="center" style="background:{{ $nuit }};padding:30px 24px 26px;border-radius:18px 18px 0 0;">
      <img src="{{ $logo }}" width="150" alt="Tiakolisé et fière" style="display:block;width:150px;height:auto;border:0;">
    </td></tr>
    <tr><td align="center" style="background:{{ $rouge }};padding:9px 16px;font-family:Archivo,'Helvetica Neue',Arial,sans-serif;font-size:11px;font-weight:800;font-style:italic;letter-spacing:.16em;text-transform:uppercase;color:{{ $creme }};">
      {{ $bandeau ?? 'Fait par nous, pour nous' }}
    </td></tr>
    {{-- contenu --}}
    <tr><td class="px" style="background:#fffaf3;padding:36px 40px 40px;font-family:Archivo,'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:1.55;color:{{ $nuit }};">
      @yield('contenu')
    </td></tr>
    {{-- pied --}}
    <tr><td align="center" style="background:{{ $nuit }};padding:26px 24px;border-radius:0 0 18px 18px;">
      <p class="display" style="margin:0;font-family:Anton,Impact,'Arial Narrow Bold',sans-serif;font-size:20px;line-height:1.15;letter-spacing:.02em;text-transform:uppercase;color:{{ $creme }};">
        <span style="color:{{ $ocre }};">Monétisez</span> les clips <span style="color:{{ $rouge }};">afro</span> francophones
      </p>
      <p style="margin:12px 0 0;font-family:Archivo,'Helvetica Neue',Arial,sans-serif;font-size:12px;color:#a8998a;">Tiakolisé et fière × Mélo Décalé · Abidjan</p>
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
