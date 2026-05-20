<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

class WorkspaceController extends Controller
{
    public function switch(Workspace $workspace): RedirectResponse
    {
        abort_unless($workspace->user_id === auth()->id(), 403);

        session(['active_workspace_id' => $workspace->id]);

        return redirect()->route('dashboard');
    }
}
