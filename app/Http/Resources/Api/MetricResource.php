<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetricResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'cpu_usage'    => $this->cpu_usage,
            'memory_usage' => $this->memory_usage,
            'memory_total' => $this->memory_total,
            'disk_usage'   => $this->disk_usage,
            'disk_total'   => $this->disk_total,
            'load_average' => $this->load_average,
            'recorded_at' => $this->recorded_at?->toISOString(),
        ];
    }
}
