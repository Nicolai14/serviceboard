<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(Request $request)
    {
        $workspace = app('activeWorkspace');
        $summary   = $this->dashboardService->getSummary($request->user(), $workspace);

        return view('dashboard.index', $summary);
    }

    public function publicShow(User $user)
    {
        abort_unless($user->dashboard_public, 404);

        $summary = $this->dashboardService->getSummary($user, null);
        $summary['viewing_public_user'] = $user;

        return view('dashboard.index', $summary);
    }
}
