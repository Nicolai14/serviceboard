<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CloudflareController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DockerController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Guest
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
});

// Authenticated
Route::middleware(['auth', 'workspace'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Workspace
    Route::post('/workspace/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspace.switch');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Global Docker overview (all servers)
    Route::get('/docker', [DockerController::class, 'index'])->name('docker.index');

    // Servers
    Route::resource('servers', ServerController::class);

    Route::prefix('servers/{server}')->name('servers.')->group(function () {
        Route::get('status-json',  [ServerController::class, 'statusJson'])->name('status-json');
        Route::post('check-online',[ServerController::class, 'checkOnline'])->name('check-online');
        Route::post('test-ssh',    [ServerController::class, 'testSsh'])->name('test-ssh');
        Route::post('poll-now',    [ServerController::class, 'pollNow'])->name('poll-now');

        // Docker sub-resource
        Route::prefix('docker')->name('docker.')->group(function () {
            Route::get('/',                                    [DockerController::class, 'serverIndex'])->name('index');
            Route::get('/status-json',                        [DockerController::class, 'statusJson'])->name('status-json');
            Route::post('/sync',                              [DockerController::class, 'syncNow'])->name('sync');
            Route::patch('/{container}/notify',               [DockerController::class, 'toggleNotify'])->name('container.notify');
        });

        // Services
        Route::prefix('services')->name('services.')->group(function () {
            Route::get('/',            [ServiceController::class, 'index'])->name('index');
            Route::get('/create',      [ServiceController::class, 'create'])->name('create');
            Route::post('/',           [ServiceController::class, 'store'])->name('store');
            Route::patch('/{service}', [ServiceController::class, 'update'])->name('update');
            Route::delete('/{service}',[ServiceController::class, 'destroy'])->name('destroy');
        });
    });

    // Cloudflare
    Route::prefix('cloudflare')->name('cloudflare.')->group(function () {
        Route::get('/',                         [CloudflareController::class, 'index'])->name('index');
        Route::get('/dns',                      [CloudflareController::class, 'dnsIndex'])->name('dns');
        Route::post('/tokens',                  [CloudflareController::class, 'storeToken'])->name('tokens.store');
        Route::delete('/tokens/{token}',        [CloudflareController::class, 'destroyToken'])->name('tokens.destroy');
        Route::post('/tokens/{token}/sync',     [CloudflareController::class, 'syncToken'])->name('tokens.sync');
        Route::get('/zones/{zone}',             [CloudflareController::class, 'zoneShow'])->name('zones.show');
        Route::get('/zones/{zone}/status-json', [CloudflareController::class, 'zoneStatusJson'])->name('zones.status-json');
        Route::post('/zones/{zone}/sync-dns',   [CloudflareController::class, 'syncDns'])->name('zones.sync-dns');
    });

    // Alerts
    Route::get('/alerts',                    [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/{alert}/read',      [AlertController::class, 'markAsRead'])->name('alerts.read');
    Route::post('/alerts/read-all',          [AlertController::class, 'markAllAsRead'])->name('alerts.read-all');
    Route::post('/alerts/{alert}/resolve',   [AlertController::class, 'resolve'])->name('alerts.resolve');
});
