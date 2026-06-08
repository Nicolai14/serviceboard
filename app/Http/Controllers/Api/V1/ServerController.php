<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MetricResource;
use App\Http\Resources\Api\ServerResource;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $servers = $request->user()
            ->servers()
            ->with('latestMetric')
            ->withCount('dockerContainers')
            ->latest()
            ->get();

        return ServerResource::collection($servers);
    }

    public function show(Request $request, Server $server): ServerResource
    {
        abort_unless($server->user_id === $request->user()->id, 403);

        $server->loadCount('dockerContainers', 'services');
        $server->load('latestMetric');

        return new ServerResource($server);
    }

    public function metrics(Request $request, Server $server): AnonymousResourceCollection
    {
        abort_unless($server->user_id === $request->user()->id, 403);

        $limit   = min((int) $request->get('limit', 60), 1440);
        $metrics = $server->metrics()
            ->latest('recorded_at')
            ->limit($limit)
            ->get();

        return MetricResource::collection($metrics);
    }
}
