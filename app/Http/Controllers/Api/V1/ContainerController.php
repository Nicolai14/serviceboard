<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ContainerResource;
use App\Models\DockerContainer;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContainerController extends Controller
{
    public function all(Request $request): AnonymousResourceCollection
    {
        $serverIds = $request->user()->servers()->pluck('id');

        $containers = DockerContainer::whereIn('server_id', $serverIds)
            ->with('server:id,name')
            ->orderBy('name')
            ->get();

        return ContainerResource::collection($containers);
    }

    public function index(Request $request, Server $server): AnonymousResourceCollection
    {
        abort_unless($server->user_id === $request->user()->id, 403);

        $containers = $server->dockerContainers()->orderBy('name')->get();

        return ContainerResource::collection($containers);
    }
}
