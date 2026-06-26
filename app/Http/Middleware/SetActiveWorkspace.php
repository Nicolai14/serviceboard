<?php

namespace App\Http\Middleware;

use App\Models\CloudflareZone;
use App\Models\Server;
use Closure;
use Illuminate\Http\Request;

class SetActiveWorkspace
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Ensure personal + business workspaces exist (idempotent)
        $user->ensureWorkspacesExist();

        // Resolve active workspace from session
        $workspaceId = session('active_workspace_id');
        $workspace   = $workspaceId ? $user->workspaces()->find($workspaceId) : null;

        // Fall back to personal workspace
        if (!$workspace) {
            $workspace = $user->workspaces()->where('type', 'personal')->first();
            session(['active_workspace_id' => $workspace->id]);
        }

        // Migrate any servers/domains without a workspace into the personal workspace
        $hasOrphanServers = $user->servers()->whereNull('workspace_id')->exists();
        $hasOrphanZones   = CloudflareZone::where('user_id', $user->id)->whereNull('workspace_id')->exists();

        if ($hasOrphanServers || $hasOrphanZones) {
            $personal = $user->workspaces()->where('type', 'personal')->first();

            if ($hasOrphanServers) {
                $user->servers()->whereNull('workspace_id')
                     ->update(['workspace_id' => $personal->id]);
            }

            if ($hasOrphanZones) {
                CloudflareZone::where('user_id', $user->id)->whereNull('workspace_id')
                    ->update(['workspace_id' => $personal->id]);
            }
        }

        view()->share('activeWorkspace', $workspace);
        app()->instance('activeWorkspace', $workspace);

        return $next($request);
    }
}
