<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function __construct(private readonly AlertService $alertService) {}

    public function index(Request $request): View
    {
        $alerts = $this->alertService->getForUser($request->user(), $request->only(['severity', 'unread', 'server_id']));

        return view('alerts.index', compact('alerts'));
    }

    public function markAsRead(Alert $alert): RedirectResponse
    {
        $this->authorize('update', $alert);

        $this->alertService->markAsRead($alert);

        return back()->with('success', 'Alert als gelesen markiert.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->alertService->markAllAsRead($request->user());

        return back()->with('success', 'Alle Alerts wurden als gelesen markiert.');
    }

    public function resolve(Alert $alert): RedirectResponse
    {
        $this->authorize('update', $alert);

        $this->alertService->resolve($alert);

        return back()->with('success', 'Alert wurde als behoben markiert.');
    }
}
