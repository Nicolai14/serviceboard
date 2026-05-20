<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AlertResource;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AlertController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $alerts = $request->user()
            ->alerts()
            ->latest()
            ->limit(50)
            ->get();

        return AlertResource::collection($alerts);
    }

    public function markRead(Request $request, Alert $alert): JsonResponse
    {
        abort_unless($alert->user_id === $request->user()->id, 403);

        $alert->update(['is_read' => true]);

        return response()->json(['message' => 'Alert marked as read.']);
    }
}
