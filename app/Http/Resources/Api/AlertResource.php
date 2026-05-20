<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'severity'   => $this->severity,
            'message'    => $this->message,
            'context'    => $this->context,
            'is_read'    => (bool) $this->is_read,
            'resolved_at'=> $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
