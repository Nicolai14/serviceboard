<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Server $server): View
    {
        $this->authorize('view', $server);

        $services = $server->services()->latest()->paginate(20);

        return view('services.index', compact('server', 'services'));
    }

    public function create(Server $server): View
    {
        $this->authorize('view', $server);

        return view('services.create', compact('server'));
    }

    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('update', $server);

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'type'           => ['required', 'in:web,database,cache,queue,proxy,mail,custom'],
            'port'           => ['nullable', 'integer', 'min:1', 'max:65535'],
            'check_url'      => ['nullable', 'url', 'max:255'],
            'check_interval' => ['nullable', 'integer', 'min:10', 'max:3600'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'notify_on_down' => ['nullable', 'boolean'],
        ]);
        $validated['notify_on_down'] = $request->boolean('notify_on_down');

        $server->services()->create($validated);

        return redirect()->route('servers.show', $server)->with('success', 'Service wurde hinzugefügt.');
    }

    public function update(Request $request, Server $server, Service $service): RedirectResponse
    {
        $this->authorize('update', $server);

        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'type'           => ['required', 'in:web,database,cache,queue,proxy,mail,custom'],
            'port'           => ['nullable', 'integer', 'min:1', 'max:65535'],
            'check_url'      => ['nullable', 'url', 'max:255'],
            'check_interval' => ['nullable', 'integer', 'min:10', 'max:3600'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'notify_on_down' => ['nullable', 'boolean'],
        ]);
        $validated['notify_on_down'] = $request->boolean('notify_on_down');

        $service->update($validated);

        return redirect()->route('servers.show', $server)->with('success', 'Service wurde aktualisiert.');
    }

    public function destroy(Server $server, Service $service): RedirectResponse
    {
        $this->authorize('update', $server);

        $service->delete();

        return redirect()->route('servers.show', $server)->with('success', 'Service wurde entfernt.');
    }
}
