<?php

use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ContainerController;
use App\Http\Controllers\Api\V1\ServerController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Auth (public)
    Route::post('/auth/token', [AuthController::class, 'createToken'])->name('auth.token');

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user'])->name('user');
        Route::delete('/auth/token', [AuthController::class, 'revokeToken'])->name('auth.revoke');

        Route::get('/servers',                    [ServerController::class, 'index'])->name('servers.index');
        Route::get('/servers/{server}',           [ServerController::class, 'show'])->name('servers.show');
        Route::get('/servers/{server}/metrics',   [ServerController::class, 'metrics'])->name('servers.metrics');

        Route::get('/containers',                 [ContainerController::class, 'all'])->name('containers.all');
        Route::get('/servers/{server}/containers',[ContainerController::class, 'index'])->name('containers.index');

        Route::get('/alerts',                     [AlertController::class, 'index'])->name('alerts.index');
        Route::post('/alerts/{alert}/read',       [AlertController::class, 'markRead'])->name('alerts.read');
    });
});
