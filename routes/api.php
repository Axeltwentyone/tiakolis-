<?php

use App\Http\Controllers\Api\CatalogueController;
use App\Http\Controllers\Api\PrecommandeController;
use Illuminate\Support\Facades\Route;

Route::get('/catalogue', CatalogueController::class);
Route::post('/precommandes', [PrecommandeController::class, 'store']);
Route::put('/precommandes/{reference}', [PrecommandeController::class, 'update']);
Route::post('/precommandes/{reference}/capture', [PrecommandeController::class, 'capture'])->middleware('throttle:10,1');
