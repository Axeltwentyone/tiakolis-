<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ligne;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PieceController extends Controller
{
    public function index(): View
    {
        return view('admin.pieces.index', ['pieces' => Produit::orderBy('ordre')->with('stocks')->get()]);
    }

    public function create(): View
    {
        return view('admin.pieces.form', ['piece' => new Produit(['type' => 'T-shirt oversize', 'actif' => true, 'ordre' => Produit::max('ordre') + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->valide($request);
        $piece = DB::transaction(function () use ($request, $data) {
            $piece = Produit::create($data);
            foreach (Produit::TAILLES as $i => $t) {
                $piece->stocks()->create(['taille' => $t, 'quantite' => (int) $request->input("stock.$t", 0), 'ordre' => $i]);
            }

            return $piece;
        });

        return redirect()->route('admin.pieces.index')->with('ok', "{$piece->libelle} ajoutée à la collection.");
    }

    public function edit(Produit $produit): View
    {
        return view('admin.pieces.form', ['piece' => $produit->load('stocks')]);
    }

    public function update(Request $request, Produit $produit): RedirectResponse
    {
        $data = $this->valide($request, $produit);
        DB::transaction(function () use ($request, $produit, $data) {
            $produit->update($data);
            // Le formulaire envoie le stock affiché à l'ouverture (stock_initial) et le nouveau (stock).
            // On applique la différence : une précommande passée pendant la saisie n'est pas écrasée.
            foreach (Produit::TAILLES as $i => $t) {
                $stock = Stock::lockForUpdate()->firstOrCreate(['produit_id' => $produit->id, 'taille' => $t], ['quantite' => 0, 'ordre' => $i]);
                $delta = (int) $request->input("stock.$t", 0) - (int) $request->input("stock_initial.$t", $stock->quantite);
                if ($delta !== 0) {
                    $stock->update(['quantite' => max(0, $stock->quantite + $delta)]);
                }
            }
        });

        return redirect()->route('admin.pieces.index')->with('ok', "{$produit->libelle} enregistrée.");
    }

    public function visibilite(Produit $produit): RedirectResponse
    {
        $produit->update(['actif' => ! $produit->actif]);

        return back()->with('ok', $produit->libelle.($produit->actif ? ' est visible sur le site.' : ' est masquée du site.'));
    }

    public function destroy(Produit $produit): RedirectResponse
    {
        // une pièce déjà précommandée se masque, elle ne se supprime pas
        if (Ligne::where('produit_id', $produit->id)->exists()) {
            return back()->with('ok', 'Cette pièce a déjà des précommandes : masque-la du site plutôt que de la supprimer.');
        }
        foreach (['image_face', 'image_dos'] as $champ) {
            if (str_starts_with((string) $produit->{$champ}, 'produits/')) {
                Storage::disk('public')->delete($produit->{$champ});
            }
        }
        $produit->delete();

        return redirect()->route('admin.pieces.index')->with('ok', "{$produit->libelle} supprimée.");
    }

    private function valide(Request $request, ?Produit $produit = null): array
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:60'],
            'couleur' => ['required', 'string', 'max:30'],
            'type' => ['required', 'string', 'max:60'],
            'prix' => ['required', 'integer', 'min:0', 'max:10000000'],
            'slug' => ['required', 'alpha_dash', 'max:60', Rule::unique('produits')->ignore($produit)],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:999'],
            'stock' => ['array'],
            'stock.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'image_face' => [$produit ? 'nullable' : 'required', 'image', 'mimes:webp,png,jpg,jpeg', 'max:4096'],
            'image_dos' => [$produit ? 'nullable' : 'required', 'image', 'mimes:webp,png,jpg,jpeg', 'max:4096'],
        ], [
            'slug.unique' => 'Cet identifiant est déjà pris par une autre pièce.',
            'slug.alpha_dash' => 'Identifiant : lettres, chiffres et tirets uniquement.',
            'image_face.required' => 'Ajoute la photo de face.',
            'image_dos.required' => 'Ajoute la photo de dos.',
            '*.image' => 'Le fichier doit être une image (webp, png ou jpg).',
            '*.max' => 'Valeur trop grande (photos : 4 Mo maximum).',
        ]);

        $data['actif'] = $request->boolean('actif');
        $data['ordre'] = (int) ($data['ordre'] ?? $produit?->ordre ?? Produit::max('ordre') + 1); // nouvelle pièce : à la fin
        unset($data['stock']);

        // nouvelle photo : on la range dans storage/app/public/produits et on supprime l'ancienne si elle venait du back office
        foreach (['image_face', 'image_dos'] as $champ) {
            if ($request->hasFile($champ)) {
                $data[$champ] = $request->file($champ)->store('produits', 'public');
                if ($produit && str_starts_with((string) $produit->{$champ}, 'produits/')) {
                    Storage::disk('public')->delete($produit->{$champ});
                }
            } else {
                unset($data[$champ]);
            }
        }

        return $data;
    }
}
