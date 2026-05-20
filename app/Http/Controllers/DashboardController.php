<?php

namespace App\Http\Controllers;

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
}
