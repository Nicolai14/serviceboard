<?php

namespace App\Providers;

use App\Contracts\NotificationDriverContract;
use App\Models\Alert;
use App\Models\Server;
use App\Policies\AlertPolicy;
use App\Policies\ServerPolicy;
use App\Services\DeploymentService;
use App\Services\NotificationService;
use App\Services\Notifications\EmailDriver;
use App\Services\Notifications\TelegramDriver;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TelegramService::class);

        $this->app->singleton(NotificationService::class, function ($app) {
            $service = new NotificationService();
            $service->registerDriver($app->make(EmailDriver::class));
            $service->registerDriver($app->make(TelegramDriver::class));
            return $service;
        });

        $this->app->singleton(DeploymentService::class);
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(Server::class, ServerPolicy::class);
        Gate::policy(Alert::class, AlertPolicy::class);
    }
}
