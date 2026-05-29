<?php

namespace App\Providers;

use App\Contracts\NotificationDriverContract;
use App\Models\Alert;
use App\Models\Server;
use App\Policies\AlertPolicy;
use App\Policies\ServerPolicy;
use App\Services\DeploymentService;
use App\Services\Deployments\SshDeploymentDriver;
use App\Services\NotificationService;
use App\Services\Notifications\EmailDriver;
use App\Services\Notifications\TelegramDriver;
use App\Services\Telegram\Commands\AlertsCommand;
use App\Services\Telegram\Commands\HelpCommand;
use App\Services\Telegram\Commands\ServersCommand;
use App\Services\Telegram\Commands\StartCommand;
use App\Services\Telegram\Commands\StatusCommand;
use App\Services\TelegramBotService;
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

        $this->app->singleton(TelegramBotService::class, function ($app) {
            $bot = new TelegramBotService($app->make(TelegramService::class));

            $commands = [
                $app->make(StartCommand::class),
                $app->make(StatusCommand::class),
                $app->make(ServersCommand::class),
                $app->make(AlertsCommand::class),
            ];

            foreach ($commands as $command) {
                $bot->registerCommand($command);
            }

            $bot->registerCommand(new HelpCommand($commands));

            return $bot;
        });

        $this->app->singleton(DeploymentService::class, function ($app) {
            $service = new DeploymentService();
            $service->registerDriver($app->make(SshDeploymentDriver::class));

            return $service;
        });
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
