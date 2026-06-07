<x-layouts.app title="Projekt Workflows">
    <div x-data="workflowCanvas(@js($graph), @js($palette), '{{ route('workflow.update') }}')"
         @pointermove.window="onMove($event)"
         @pointerup.window="onUp()"
         @keydown.window="onKey($event)"
         class="flex flex-col h-[calc(100vh-7rem)]">

        {{-- ------------------------------------------------------------- --}}
        {{-- Header / toolbar                                               --}}
        {{-- ------------------------------------------------------------- --}}
        <div class="mb-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-100">Projekt Workflow</h2>
                    <p class="text-sm text-zinc-500 mt-0.5">
                        Bausteine platzieren, benennen und verbinden — so siehst du den Aufbau deines Projekts.
                        <span class="text-zinc-600">({{ $workspace->type->icon() }} {{ $workspace->name }})</span>
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <span x-show="dirty" x-cloak class="flex items-center gap-1.5 text-xs text-amber-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                        Ungespeicherte Änderungen
                    </span>
                    <span x-show="!dirty && savedOnce" x-cloak class="flex items-center gap-1.5 text-xs text-emerald-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        Gespeichert
                    </span>
                    <button @click="save()" :disabled="saving || !dirty"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                        <span x-show="!saving">Speichern</span>
                        <span x-show="saving" x-cloak>Speichern…</span>
                    </button>
                </div>
            </div>

            {{-- Block palette --}}
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-600 mr-1">Baustein:</span>
                <template x-for="p in palette" :key="p.value">
                    <button @click="addNode(p.value)"
                            class="flex items-center gap-1.5 rounded-lg border border-zinc-800 bg-zinc-900 px-2.5 py-1.5 text-xs font-medium text-zinc-300 hover:border-zinc-600 hover:text-zinc-100 transition-colors">
                        <span x-text="p.icon" class="text-sm leading-none"></span>
                        <span x-text="p.label"></span>
                        <svg class="h-3 w-3 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                </template>
            </div>
        </div>

        {{-- ------------------------------------------------------------- --}}
        {{-- Editor: canvas + inspector                                     --}}
        {{-- ------------------------------------------------------------- --}}
        <div class="flex flex-1 min-h-0 gap-4">

            {{-- Canvas --}}
            <div class="relative flex-1 overflow-auto rounded-xl border border-zinc-800 bg-zinc-950">
                <div x-ref="canvas"
                     class="relative"
                     :style="`width:${canvasW}px; height:${canvasH}px;
                              background-image: radial-gradient(circle, rgb(63 63 70 / 0.35) 1px, transparent 1px);
                              background-size: 22px 22px;`"
                     @pointerdown.self="onCanvasClick()">

                    {{-- Empty state --}}
                    <div x-show="nodes.length === 0" x-cloak
                         class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-sm text-zinc-500">Noch keine Bausteine.</p>
                            <p class="text-xs text-zinc-600 mt-1">Wähle oben einen Baustein, um zu starten.</p>
                        </div>
                    </div>

                    {{-- Connections (SVG) --}}
                    <svg class="absolute inset-0 pointer-events-none" :width="canvasW" :height="canvasH">
                        <defs>
                            <marker id="wf-arrow" viewBox="0 0 10 10" refX="9" refY="5"
                                    markerWidth="7" markerHeight="7" orient="auto-start-reverse">
                                <path d="M 0 0 L 10 5 L 0 10 z" fill="#52525b"/>
                            </marker>
                        </defs>
                        <template x-for="edge in edges" :key="edge.id">
                            <g>
                                {{-- Wide invisible hit-area --}}
                                <path :d="edgePath(edge)" stroke="transparent" stroke-width="16" fill="none"
                                      class="pointer-events-auto cursor-pointer" @click="selectEdge(edge)"/>
                                {{-- Visible line --}}
                                <path :d="edgePath(edge)" fill="none" marker-end="url(#wf-arrow)"
                                      :stroke="edge.id === selectedEdgeId ? '#818cf8' : '#52525b'"
                                      :stroke-width="edge.id === selectedEdgeId ? 2.5 : 2"/>
                            </g>
                        </template>
                    </svg>

                    {{-- Edge labels / delete badges --}}
                    <template x-for="edge in edges" :key="'lbl-'+edge.id">
                        <div class="absolute -translate-x-1/2 -translate-y-1/2"
                             :style="`left:${edgeMid(edge).x}px; top:${edgeMid(edge).y}px`">
                            <button @click.stop="selectEdge(edge)"
                                    x-show="edge.label || edge.id === selectedEdgeId"
                                    class="rounded-full border px-2 py-0.5 text-[11px] font-medium transition-colors"
                                    :class="edge.id === selectedEdgeId
                                        ? 'border-indigo-500/60 bg-indigo-600/20 text-indigo-300'
                                        : 'border-zinc-700 bg-zinc-900 text-zinc-400 hover:text-zinc-200'"
                                    x-text="edge.label || 'Verbindung'"></button>
                        </div>
                    </template>

                    {{-- Nodes --}}
                    <template x-for="node in nodes" :key="node.id">
                        <div class="absolute"
                             :style="`left:${node.x}px; top:${node.y}px; width:${nodeW}px`"
                             @pointerdown.stop="onNodePointerDown(node, $event)">
                            <div class="group relative rounded-xl border bg-zinc-900 shadow-lg transition-shadow"
                                 :class="[
                                     node.id === selectedId ? 'ring-2 ring-indigo-500' : '',
                                     connectFrom !== null && connectFrom !== node.id ? 'cursor-pointer hover:ring-2 hover:ring-emerald-500' : 'cursor-move',
                                 ]"
                                 :style="`border-color:${colorFor(node.type)}55`">

                                {{-- Accent bar --}}
                                <div class="h-1 rounded-t-xl" :style="`background:${colorFor(node.type)}`"></div>

                                <div class="flex items-start gap-2.5 px-3 py-2.5">
                                    <span x-text="iconFor(node.type)" class="text-lg leading-none mt-0.5 shrink-0"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-zinc-100"
                                           x-text="node.label || 'Unbenannt'"></p>
                                        <p class="text-[11px] uppercase tracking-wide" :style="`color:${colorFor(node.type)}`"
                                           x-text="labelFor(node.type)"></p>
                                    </div>
                                </div>

                                {{-- Connection handle --}}
                                <button @pointerdown.stop.prevent="beginConnect(node)"
                                        title="Verbindung ziehen"
                                        class="absolute -right-2.5 top-1/2 -translate-y-1/2 flex h-5 w-5 items-center justify-center rounded-full border-2 border-zinc-950 text-white shadow transition-transform hover:scale-110"
                                        :style="`background:${colorFor(node.type)}`">
                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 12h15"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Connect-mode hint --}}
                <div x-show="connectFrom !== null" x-cloak
                     class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full border border-emerald-600/40 bg-emerald-900/40 px-4 py-1.5 text-xs text-emerald-300 backdrop-blur">
                    Ziel-Baustein anklicken — oder <kbd class="rounded bg-zinc-800 px-1">Esc</kbd> zum Abbrechen
                </div>
            </div>

            {{-- Inspector --}}
            <div class="hidden lg:flex w-72 shrink-0 flex-col rounded-xl border border-zinc-800 bg-zinc-900 p-4 overflow-y-auto">

                {{-- Node selected --}}
                <template x-if="selectedNode()">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <span x-text="iconFor(selectedNode().type)" class="text-xl"></span>
                            <span class="text-sm font-semibold text-zinc-100" x-text="labelFor(selectedNode().type)"></span>
                        </div>

                        <label class="block text-xs font-medium text-zinc-500 mb-1">Name</label>
                        <input type="text" maxlength="120" x-model="selectedNode().label" @input="markDirty()"
                               placeholder="z. B. HorseFlow Backend"
                               class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-100 placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">

                        <label class="block text-xs font-medium text-zinc-500 mb-1 mt-3">URL / Adresse (optional)</label>
                        <input type="text" maxlength="255" x-model="selectedNode().meta.url" @input="markDirty()"
                               placeholder="z. B. https://…"
                               class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-300 placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">

                        <label class="block text-xs font-medium text-zinc-500 mb-1 mt-3">Notiz (optional)</label>
                        <textarea rows="3" maxlength="500" x-model="selectedNode().meta.note" @input="markDirty()"
                                  placeholder="Details…"
                                  class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-300 placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"></textarea>

                        <button @click="beginConnect(selectedNode())"
                                class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 12h15"/>
                            </svg>
                            Verbindung ziehen
                        </button>

                        <button @click="deleteNode(selectedNode().id)"
                                class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg border border-red-900/60 px-3 py-2 text-sm font-medium text-red-400 hover:bg-red-900/20 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                            </svg>
                            Baustein löschen
                        </button>
                    </div>
                </template>

                {{-- Edge selected --}}
                <template x-if="selectedEdge()">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 12h15"/>
                            </svg>
                            <span class="text-sm font-semibold text-zinc-100">Verbindung</span>
                        </div>
                        <p class="text-xs text-zinc-500 mb-3">
                            <span x-text="nodeName(selectedEdge().from)"></span>
                            <span class="text-zinc-600"> → </span>
                            <span x-text="nodeName(selectedEdge().to)"></span>
                        </p>

                        <label class="block text-xs font-medium text-zinc-500 mb-1">Beschriftung (optional)</label>
                        <input type="text" maxlength="80" x-model="selectedEdge().label" @input="markDirty()"
                               placeholder="z. B. HTTPS, SMTP, API"
                               class="w-full rounded-lg border px-3 py-2 text-sm bg-zinc-800 border-zinc-700 text-zinc-100 placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">

                        <button @click="deleteEdge(selectedEdge().id)"
                                class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg border border-red-900/60 px-3 py-2 text-sm font-medium text-red-400 hover:bg-red-900/20 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79"/>
                            </svg>
                            Verbindung löschen
                        </button>
                    </div>
                </template>

                {{-- Nothing selected --}}
                <template x-if="!selectedNode() && !selectedEdge()">
                    <div class="text-sm text-zinc-500">
                        <p class="font-medium text-zinc-400 mb-2">Editor</p>
                        <ul class="space-y-1.5 text-xs leading-relaxed text-zinc-500">
                            <li>• Baustein oben hinzufügen</li>
                            <li>• Ziehen zum Verschieben</li>
                            <li>• Pfeil-Punkt (➔) ziehen für Verbindungen</li>
                            <li>• Anklicken zum Bearbeiten</li>
                        </ul>
                        <div class="mt-4 border-t border-zinc-800 pt-3">
                            <p class="text-xs font-medium text-zinc-500 mb-2">Legende</p>
                            <div class="space-y-1.5">
                                <template x-for="p in palette" :key="p.value">
                                    <div class="flex items-center gap-2 text-xs text-zinc-400">
                                        <span class="h-2.5 w-2.5 rounded-full" :style="`background:${p.color}`"></span>
                                        <span x-text="p.icon"></span>
                                        <span x-text="p.label"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('workflowCanvas', (graph, palette, saveUrl) => ({
                // ---- config ----
                canvasW: 2400,
                canvasH: 1500,
                nodeW: 184,
                nodeH: 64,
                palette,
                saveUrl,

                // ---- state ----
                nodes: (graph.nodes || []).map(n => ({
                    id: n.id,
                    type: n.type,
                    label: n.label || '',
                    x: n.x,
                    y: n.y,
                    meta: Object.assign({ url: '', note: '' }, n.meta || {}),
                })),
                edges: (graph.edges || []).map(e => ({
                    id: e.id,
                    from: e.from,
                    to: e.to,
                    label: e.label || '',
                })),
                selectedId: null,
                selectedEdgeId: null,
                connectFrom: null,
                dragId: null,
                dragOffset: { x: 0, y: 0 },
                moved: false,
                dirty: false,
                saving: false,
                savedOnce: false,
                _tmp: 0,

                // ---- type helpers ----
                meta(type) { return this.palette.find(p => p.value === type) || {}; },
                iconFor(type) { return this.meta(type).icon || '⬛'; },
                labelFor(type) { return this.meta(type).label || type; },
                colorFor(type) { return this.meta(type).color || '#71717a'; },

                // ---- lookups ----
                nodeById(id) { return this.nodes.find(n => n.id === id); },
                selectedNode() { return this.selectedId === null ? null : this.nodeById(this.selectedId); },
                selectedEdge() { return this.selectedEdgeId === null ? null : this.edges.find(e => e.id === this.selectedEdgeId); },
                nodeName(id) { const n = this.nodeById(id); return n ? (n.label || this.labelFor(n.type)) : '—'; },

                // ---- geometry ----
                center(node) { return { x: node.x + this.nodeW / 2, y: node.y + this.nodeH / 2 }; },
                edgePath(edge) {
                    const a = this.nodeById(edge.from), b = this.nodeById(edge.to);
                    if (!a || !b) return '';
                    const p = this.center(a), q = this.center(b);
                    const c = Math.max(40, Math.abs(q.x - p.x) / 2);
                    return `M ${p.x} ${p.y} C ${p.x + c} ${p.y}, ${q.x - c} ${q.y}, ${q.x} ${q.y}`;
                },
                edgeMid(edge) {
                    const a = this.nodeById(edge.from), b = this.nodeById(edge.to);
                    if (!a || !b) return { x: 0, y: 0 };
                    const p = this.center(a), q = this.center(b);
                    return { x: (p.x + q.x) / 2, y: (p.y + q.y) / 2 };
                },
                canvasPoint(e) {
                    const r = this.$refs.canvas.getBoundingClientRect();
                    return { x: e.clientX - r.left, y: e.clientY - r.top };
                },

                // ---- mutations ----
                markDirty() { this.dirty = true; this.savedOnce = false; },

                addNode(type) {
                    const id = 'tmp-' + (++this._tmp);
                    const n = this.nodes.length;
                    this.nodes.push({
                        id, type, label: '',
                        x: 80 + (n % 6) * 36,
                        y: 80 + (n % 6) * 36,
                        meta: { url: '', note: '' },
                    });
                    this.selectedId = id;
                    this.selectedEdgeId = null;
                    this.markDirty();
                },

                onNodePointerDown(node, e) {
                    if (this.connectFrom !== null) {
                        this.completeConnect(node);
                        return;
                    }
                    this.selectedId = node.id;
                    this.selectedEdgeId = null;
                    this.dragId = node.id;
                    this.moved = false;
                    const p = this.canvasPoint(e);
                    this.dragOffset = { x: p.x - node.x, y: p.y - node.y };
                },

                onMove(e) {
                    if (this.dragId === null) return;
                    const node = this.nodeById(this.dragId);
                    if (!node) return;
                    const p = this.canvasPoint(e);
                    const nx = Math.max(0, Math.min(this.canvasW - this.nodeW, p.x - this.dragOffset.x));
                    const ny = Math.max(0, Math.min(this.canvasH - this.nodeH, p.y - this.dragOffset.y));
                    if (nx !== node.x || ny !== node.y) { this.moved = true; this.markDirty(); }
                    node.x = nx;
                    node.y = ny;
                },

                onUp() { this.dragId = null; },

                beginConnect(node) {
                    this.connectFrom = node.id;
                    this.selectedId = node.id;
                    this.selectedEdgeId = null;
                },

                completeConnect(target) {
                    const from = this.connectFrom;
                    this.connectFrom = null;
                    if (target.id === from) return;
                    const exists = this.edges.some(e =>
                        (e.from === from && e.to === target.id) || (e.from === target.id && e.to === from));
                    if (exists) { this.selectedId = target.id; return; }
                    this.edges.push({ id: 'tmp-e-' + (++this._tmp), from, to: target.id, label: '' });
                    this.markDirty();
                },

                selectEdge(edge) {
                    this.selectedEdgeId = edge.id;
                    this.selectedId = null;
                    this.connectFrom = null;
                },

                onCanvasClick() {
                    if (this.connectFrom !== null) { this.connectFrom = null; return; }
                    this.selectedId = null;
                    this.selectedEdgeId = null;
                },

                onKey(e) {
                    if (e.key === 'Escape') {
                        this.connectFrom = null;
                        this.selectedId = null;
                        this.selectedEdgeId = null;
                        return;
                    }
                    const tag = (e.target.tagName || '').toLowerCase();
                    if ((e.key === 'Delete' || e.key === 'Backspace') && tag !== 'input' && tag !== 'textarea') {
                        if (this.selectedId !== null) { this.deleteNode(this.selectedId); e.preventDefault(); }
                        else if (this.selectedEdgeId !== null) { this.deleteEdge(this.selectedEdgeId); e.preventDefault(); }
                    }
                },

                deleteNode(id) {
                    this.nodes = this.nodes.filter(n => n.id !== id);
                    this.edges = this.edges.filter(e => e.from !== id && e.to !== id);
                    if (this.selectedId === id) this.selectedId = null;
                    this.markDirty();
                },

                deleteEdge(id) {
                    this.edges = this.edges.filter(e => e.id !== id);
                    if (this.selectedEdgeId === id) this.selectedEdgeId = null;
                    this.markDirty();
                },

                // ---- persistence ----
                async save() {
                    if (this.saving) return;
                    this.saving = true;
                    try {
                        const res = await fetch(this.saveUrl, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                nodes: this.nodes.map(n => ({
                                    id: n.id, type: n.type, label: n.label,
                                    x: Math.round(n.x), y: Math.round(n.y),
                                    meta: { url: n.meta.url || '', note: n.meta.note || '' },
                                })),
                                edges: this.edges.map(e => ({ from: e.from, to: e.to, label: e.label })),
                            }),
                        });
                        if (!res.ok) throw new Error('Speichern fehlgeschlagen (' + res.status + ')');
                        const data = await res.json();
                        // Re-hydrate from the server so client ids become real ids.
                        const keepSel = this.selectedNode()?.label;
                        this.nodes = data.graph.nodes.map(n => ({
                            id: n.id, type: n.type, label: n.label || '',
                            x: n.x, y: n.y,
                            meta: Object.assign({ url: '', note: '' }, n.meta || {}),
                        }));
                        this.edges = data.graph.edges.map(e => ({
                            id: e.id, from: e.from, to: e.to, label: e.label || '',
                        }));
                        this.selectedId = null;
                        this.selectedEdgeId = null;
                        this.dirty = false;
                        this.savedOnce = true;
                    } catch (err) {
                        alert(err.message || 'Speichern fehlgeschlagen.');
                    } finally {
                        this.saving = false;
                    }
                },
            }));
        });
    </script>
    @endpush
</x-layouts.app>
