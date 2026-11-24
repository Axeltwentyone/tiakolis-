<?php

use App\Http\Controllers\Api\CatalogueController;
use App\Http\Controllers\Api\PrecommandeController;
use App\Http\Controllers\JekoController;
use Illuminate\Support\Facades\Route;

Route::get('/catalogue', CatalogueController::class);
// aucune précommande sans paiement : vérification (rien n'est enregistré), puis création avec la capture du paiement
Route::post('/precommandes/verifier', [PrecommandeController::class, 'verifier'])->middleware('throttle:30,1');
Route::post('/precommandes', [PrecommandeController::class, 'store'])->middleware('throttle:10,1');
// paiement en ligne Jeko : départ vers la page de paiement, puis confirmation par webhook signé
Route::post('/paiements/jeko', [JekoController::class, 'creer'])->middleware('throttle:20,1');
Route::post('/jeko/webhook', [JekoController::class, 'webhook'])->middleware('throttle:120,1');
