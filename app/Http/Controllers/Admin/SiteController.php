<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** « Photos du site » : logo, colonnes du premier écran, mur de télés, image de partage. */
class SiteController extends Controller
{
    private const IMAGE = 'mimes:jpg,jpeg,png,webp|max:10240';

    private const IMAGE_OU_VIDEO = 'mimes:jpg,jpeg,png,webp,mp4,webm,mov|max:10240';

    private const VIDEO = 'mimes:mp4,webm,mov|max:10240';

    private const NOMS = ['logo' => 'Logo', 'hero' => 'Colonne', 'tv_video' => 'Télé vidéo', 'tv_image' => 'Image des télés', 'partage' => 'Image de partage'];

    public function index(): View
    {
        return view('admin.site', ['m' => Media::duSite()]);
    }

    public function remplacer(Request $request, Media $media): RedirectResponse
    {
        $regles = match ($media->zone) {
            'hero' => ['fichier' => 'nullable|file|'.self::IMAGE_OU_VIDEO, 'poster' => 'nullable|file|'.self::IMAGE, 'legende' => 'nullable|string|max:40'],
            'tv_video' => ['fichier' => 'nullable|file|'.self::VIDEO, 'poster' => 'nullable|file|'.self::IMAGE],
            default => ['fichier' => 'required|file|'.self::IMAGE],
        };
        $data = $request->validate($regles, [
            '*.mimes' => 'Format non accepté : photo en jpg, png ou webp, vidéo en mp4.',
            '*.max' => 'Fichier trop lourd : 10 Mo maximum.',
            'fichier.required' => 'Choisis un fichier.',
        ]);

        if ($request->hasFile('fichier')) {
            $ancien = $media->fichier;
            $media->fichier = $request->file('fichier')->store('site', 'public');
            $this->supprimerFichier($ancien);
            // une photo n'a pas besoin d'image d'attente
            if ($media->zone === 'hero' && ! $media->est_video) {
                $this->supprimerFichier($media->poster);
                $media->poster = null;
            }
        }
        if ($request->hasFile('poster')) {
            $ancien = $media->poster;
            $media->poster = $request->file('poster')->store('site', 'public');
            $this->supprimerFichier($ancien);
        }
        if (array_key_exists('legende', $data)) {
            $media->legende = filled($data['legende']) ? trim($data['legende']) : null;
        }
        $media->save();

        $nom = self::NOMS[$media->zone].($media->zone === 'hero' ? ' '.($media->ordre + 1) : '');

        return back()->with('ok', "{$nom} : enregistré. C'est en ligne.");
    }

    public function ajouterTv(Request $request): RedirectResponse
    {
        $request->validate(
            ['images' => 'required|array|max:20', 'images.*' => 'file|'.self::IMAGE],
            ['images.required' => 'Choisis au moins une image.', 'images.*.mimes' => 'Images en jpg, png ou webp uniquement.', 'images.*.max' => 'Image trop lourde : 10 Mo maximum.'],
        );
        $ordre = (int) Media::where('zone', 'tv_image')->max('ordre');
        foreach ($request->file('images') as $image) {
            Media::create(['zone' => 'tv_image', 'ordre' => ++$ordre, 'fichier' => $image->store('site', 'public')]);
        }

        return back()->with('ok', count($request->file('images')).' image(s) ajoutée(s) au mur de télés.');
    }

    public function supprimer(Media $media): RedirectResponse
    {
        abort_unless($media->zone === 'tv_image', 403);
        if (Media::where('zone', 'tv_image')->count() <= 1) {
            return back()->with('ok', 'Il faut garder au moins une image sur le mur de télés.');
        }
        $this->supprimerFichier($media->fichier);
        $media->delete();

        return back()->with('ok', 'Image retirée du mur de télés.');
    }

    /** Seuls les fichiers envoyés depuis le back office sont effacés, jamais ceux livrés avec le site. */
    private function supprimerFichier(?string $chemin): void
    {
        if ($chemin && str_starts_with($chemin, 'site/')) {
            Storage::disk('public')->delete($chemin);
        }
    }
}
