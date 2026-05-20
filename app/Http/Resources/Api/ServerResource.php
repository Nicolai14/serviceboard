<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'hostname'    => $this->hostname,
            'ip_address'  => $this->ip_address,
            'status'      => $this->status,
            'os'          => $this->os,
            'tags'        => $this->tags ?? [],
            'last_seen_at'   => $this->last_seen_at?->toISOString(),
            'last_polled_at' => $this->last_polled_at?->toISOString(),
            'containers_count' => $this->whenCounted('dockerContainers'),
            'services_count'   => $this->whenCounted('services'),
            'latest_metric' => $this->whenLoaded('metrics', function () {
                $metric = $this->metrics->first();
                return $metric ? new MetricResource($metric) : null;
            }),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}
