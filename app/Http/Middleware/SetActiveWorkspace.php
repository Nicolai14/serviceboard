<?php

namespace App\Http\Middleware;

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

        // Migrate any servers without a workspace into the personal workspace
        if ($user->servers()->whereNull('workspace_id')->exists()) {
            $personal = $user->workspaces()->where('type', 'personal')->first();
            $user->servers()->whereNull('workspace_id')
                 ->update(['workspace_id' => $personal->id]);
        }

        view()->share('activeWorkspace', $workspace);
        app()->instance('activeWorkspace', $workspace);

        return $next($request);
    }
}
