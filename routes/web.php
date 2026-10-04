<?php

use App\Http\Controllers\Admin\ConnexionController;
use App\Http\Controllers\Admin\PieceController;
use App\Http\Controllers\Admin\PrecommandeController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\TableauController;
use Illuminate\Support\Facades\Route;

// le site public ; l'API est dans routes/api.php
Route::view('/', 'site');

// back office
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('connexion', [ConnexionController::class, 'formulaire'])->name('connexion');
        Route::post('connexion', [ConnexionController::class, 'connecte']);
    });

    Route::middleware('auth')->group(function () {
        Route::post('deconnexion', [ConnexionController::class, 'deconnecte'])->name('deconnexion');
        Route::get('/', TableauController::class)->name('tableau');

        Route::get('precommandes', [PrecommandeController::class, 'index'])->name('precommandes.index');
        Route::get('precommandes/export', [PrecommandeController::class, 'export'])->name('precommandes.export');
        Route::get('precommandes/{precommande}', [PrecommandeController::class, 'show'])->name('precommandes.show');
        Route::get('precommandes/{precommande}/capture', [PrecommandeController::class, 'capture'])->name('precommandes.capture');
        Route::patch('precommandes/{precommande}/statut', [PrecommandeController::class, 'statut'])->name('precommandes.statut');
        Route::patch('precommandes/{precommande}', [PrecommandeController::class, 'update'])->name('precommandes.update');
        Route::delete('precommandes/{precommande}', [PrecommandeController::class, 'destroy'])->name('precommandes.destroy');

        // aperçu des e-mails sur la dernière commande (en local uniquement) : /admin/apercu-mail/client|paiement|commande
        if (app()->isLocal()) {
            Route::get('apercu-mail/{type}', function (string $type) {
                $p = \App\Models\Precommande::latest('id')->firstOrFail();

                return match ($type) {
                    'client' => new \App\Mail\PrecommandeRecue($p),
                    'paiement' => new \App\Mail\CaptureRecue($p),
                    'commande' => new \App\Mail\NouvellePrecommande($p),
                    'valide' => new \App\Mail\PaiementValide($p),
                    'valide-equipe' => new \App\Mail\PaiementValideEquipe($p, auth()->user()->name),
                    default => abort(404),
                };
            });
        }

        Route::resource('pieces', PieceController::class)->except('show')->parameters(['pieces' => 'produit']);
        Route::get('site', [SiteController::class, 'index'])->name('site');
        Route::post('site/tv', [SiteController::class, 'ajouterTv'])->name('site.tv');
        Route::put('site/{media}', [SiteController::class, 'remplacer'])->name('site.remplacer');
        Route::delete('site/{media}', [SiteController::class, 'supprimer'])->name('site.supprimer');
        Route::patch('pieces/{produit}/visibilite', [PieceController::class, 'visibilite'])->name('pieces.visibilite');
    });
});
