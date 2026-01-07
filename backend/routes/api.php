<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test-redis', function () {
    \Illuminate\Support\Facades\Cache::put('test_key', 'redis_working', 60);
    return [
        'value' => \Illuminate\Support\Facades\Cache::get('test_key'),
        'store' => config('cache.default'),
    ];
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('personages', \App\Http\Controllers\PersonageController::class);
    Route::post('/personages/{personage}/glb', [\App\Http\Controllers\PersonageController::class, 'uploadGlb']);
    Route::apiResource('locaties', \App\Http\Controllers\LocatieController::class)
        ->parameters(['locaties' => 'locatie']);
    Route::post('/locaties/{locatie}/glb', [\App\Http\Controllers\LocatieController::class, 'uploadGlb']);
    Route::post('/locaties/reorder', [\App\Http\Controllers\LocatieController::class, 'reorder']);
    Route::apiResource('aanwijzingen', \App\Http\Controllers\AanwijzingController::class)
        ->parameters(['aanwijzingen' => 'aanwijzing']);

    Route::apiResource('sectoren', \App\Http\Controllers\SectorController::class)
        ->parameters(['sectoren' => 'sector']);

    Route::apiResource('scenes', \App\Http\Controllers\SceneController::class)
        ->parameters(['scenes' => 'scene']);

    Route::apiResource('notities', \App\Http\Controllers\NotitieController::class)
        ->parameters(['notities' => 'notitie']);

    Route::apiResource('dialogen', \App\Http\Controllers\DialoogController::class)
        ->parameters(['dialogen' => 'dialoog']);
    Route::post('/dialogen/{dialoog}/check-options', [\App\Http\Controllers\DialoogController::class, 'checkOptions']);
    Route::post('/dialogen/{dialoog}/perform-action', [\App\Http\Controllers\DialoogController::class, 'performAction']);

    Route::apiResource('gedrag', \App\Http\Controllers\GedragController::class)
        ->parameters(['gedrag' => 'gedrag']);

    Route::apiResource('scene-personages', \App\Http\Controllers\ScenePersonageController::class);

    Route::delete('/artwork/{afbeelding}', [\App\Http\Controllers\AfbeeldingController::class, 'destroy']);
    Route::get('/afbeeldingen', [\App\Http\Controllers\AfbeeldingController::class, 'index']);

    Route::post('/upload/{modelType}/{modelId}', [\App\Http\Controllers\AfbeeldingUploadController::class, 'upload']);

    Route::apiResource('instellingen', \App\Http\Controllers\Api\InstellingController::class)->only(['index', 'show', 'update']);

    Route::post('/game/export', [\App\Http\Controllers\SystemController::class, 'exportGame']);
    Route::post('/game/artwork-backup', [\App\Http\Controllers\SystemController::class, 'artworkBackup']);
    Route::post('/game/artwork-install', [\App\Http\Controllers\SystemController::class, 'artworkInstall']);
});
