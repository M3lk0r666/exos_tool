<?php

use App\Http\Controllers\Api\V1\CaptureApiController;
use App\Http\Controllers\Api\V1\CatalogApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API REST v1 (sección 5.10) — tokens desde el perfil (Jetstream API Tokens)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('clients', [CatalogApiController::class, 'clients']);
    Route::get('devices', [CatalogApiController::class, 'devices']);

    Route::get('captures', [CaptureApiController::class, 'index']);
    Route::post('captures', [CaptureApiController::class, 'store']);
    Route::get('captures/{capture}', [CaptureApiController::class, 'show']);
    Route::get('captures/{capture}/findings', [CaptureApiController::class, 'findings']);
    Route::get('captures/{capture}/metrics', [CaptureApiController::class, 'metrics']);
});
