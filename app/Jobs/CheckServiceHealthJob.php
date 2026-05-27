<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\ServiceHealthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckServiceHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 20;

    public function __construct(public readonly int $serviceId) {}

    public function handle(ServiceHealthService $health): void
    {
        $service = Service::find($this->serviceId);

        if (!$service) {
            return;
        }

        $health->check($service);
    }
}
