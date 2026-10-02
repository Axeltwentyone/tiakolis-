{{-- Récapitulatif des pièces : photo sur fond terre cuite, comme sur le site --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:2px solid #0d0907;">
@foreach ($p->lignes as $l)
    @php
        $fichier = $l->produit ? \App\Models\Produit::fichierImage($l->produit->image_face) : null;
        $img = $fichier && isset($message) ? $message->embed($fichier) : null;
    @endphp
    <tr>
        <td width="76" style="padding:14px 14px 14px 0;border-bottom:1px solid #e6dccd;vertical-align:middle;">
            @if ($img)
                <table role="presentation" cellpadding="0" cellspacing="0"><tr><td width="64" height="64" align="center" style="background:#c4743c;border-radius:10px;"><img src="{{ $img }}" width="56" alt="" style="display:block;width:56px;height:auto;border:0;"></td></tr></table>
            @endif
        </td>
        <td style="padding:14px 0;border-bottom:1px solid #e6dccd;vertical-align:middle;">
            <p style="margin:0;font-weight:800;font-size:16px;line-height:1.25;">{{ $l->produit ? $l->produit->type.' '.$l->produit->nom : $l->libelle }}</p>
            <p style="margin:3px 0 0;font-size:14px;color:#6b5f55;">{{ $l->produit?->couleur }} · Taille {{ $l->taille }} · × {{ $l->quantite }}</p>
        </td>
        <td align="right" style="padding:14px 0 14px 12px;border-bottom:1px solid #e6dccd;vertical-align:middle;font-weight:800;font-size:16px;white-space:nowrap;">{{ fcfa($l->sous_total) }}</td>
    </tr>
@endforeach
</table>
