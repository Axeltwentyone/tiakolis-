<?php

use App\Http\Controllers\Api\CatalogueController;
use App\Http\Controllers\Api\PrecommandeController;
use Illuminate\Support\Facades\Route;

Route::get('/catalogue', CatalogueController::class);
// aucune précommande sans paiement : vérification (rien n'est enregistré), puis création avec la capture du paiement
Route::post('/precommandes/verifier', [PrecommandeController::class, 'verifier'])->middleware('throttle:30,1');
Route::post('/precommandes', [PrecommandeController::class, 'store'])->middleware('throttle:10,1');
