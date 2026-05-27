<?php

namespace App\Jobs;

use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckAllServicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Fan out one job per service whose check interval has elapsed.
        Service::query()
            ->checkable()
            ->get()
            ->filter(fn (Service $service) => $service->isCheckDue())
            ->each(fn (Service $service) => CheckServiceHealthJob::dispatch($service->id)->onQueue('monitoring'));
    }
}
