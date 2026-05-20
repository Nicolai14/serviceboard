<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContainerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'image'       => $this->image,
            'state'       => $this->state,
            'status'      => $this->status,
            'cpu_percent' => $this->cpu_percent,
            'mem_usage'   => $this->memory_usage_mb,
            'mem_limit'   => $this->memory_limit_mb,
            'ports'       => $this->ports,
            'server'      => $this->whenLoaded('server', fn () => [
                'id'   => $this->server->id,
                'name' => $this->server->name,
            ]),
            'synced_at'   => $this->synced_at?->toISOString(),
        ];
    }
}
