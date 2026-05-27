<x-layouts.app title="Deployments — {{ $server->name }}">
    @php
        $badge = [
            'yellow' => 'bg-yellow-900/30 text-yellow-400',
            'blue'   => 'bg-blue-900/30 text-blue-400',
            'green'  => 'bg-green-900/30 text-green-400',
            'red'    => 'bg-red-900/30 text-red-400',
            'zinc'   => 'bg-zinc-800 text-zinc-400',
        ];
    @endphp

    <div class="max-w-4xl">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('servers.show', $server) }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">
                ← Zurück zu {{ $server->name }}
            </a>
            <a href="{{ route('servers.deployments.create', $server) }}"
               class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Neues Deployment
            </a>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900">
            <div class="p-5 border-b border-zinc-800">
                <h2 class="text-sm font-semibold text-zinc-100">Deployments ({{ $deployments->total() }})</h2>
            </div>

            @if ($deployments->isEmpty())
                <div class="py-12 text-center">
                    <p class="text-sm text-zinc-600">Noch keine Deployments ausgeführt</p>
                </div>
            @else
                <ul class="divide-y divide-zinc-800">
                    @foreach ($deployments as $deployment)
                        <li>
                            <a href="{{ route('servers.deployments.show', [$server, $deployment]) }}"
                               class="flex items-center gap-4 px-5 py-3 hover:bg-zinc-800/40 transition-colors">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-zinc-100 truncate">{{ $deployment->name }}</p>
                                    <p class="text-xs text-zinc-500">
                                        {{ $deployment->deployment_type->label() }} · {{ ucfirst($deployment->trigger) }}
                                        @if ($deployment->duration !== null) · {{ $deployment->duration }}s @endif
                                    </p>
                                </div>
                                <span class="text-xs text-zinc-600">{{ $deployment->created_at?->diffForHumans() }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $badge[$deployment->deployment_status->color()] ?? $badge['zinc'] }}">
                                    {{ $deployment->deployment_status->label() }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($deployments->hasPages())
            <div class="mt-4">
                {{ $deployments->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
