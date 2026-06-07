<?php

namespace App\Http\Controllers;

use App\Models\CostItem;
use App\Services\CostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CostController extends Controller
{
    public function __construct(private readonly CostService $costs) {}

    /**
     * GET /costs
     * Auto-listed cost overview of all servers and domains, plus manual items.
     */
    public function index(Request $request): View
    {
        $workspace = app('activeWorkspace');

        $this->costs->sync($request->user(), $workspace);

        $overview = $this->costs->overview($workspace);

        return view('costs.index', $overview + ['workspace' => $workspace]);
    }

    /**
     * PATCH /costs
     * Bulk-save the monthly prices and notes for every row at once.
     */
    public function update(Request $request): RedirectResponse
    {
        $workspace = app('activeWorkspace');

        $validated = $request->validate([
            'items'                 => ['array'],
            'items.*.monthly_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'items.*.notes'         => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['items'] ?? [] as $id => $data) {
            $item = CostItem::where('workspace_id', $workspace->id)->find($id);

            if (! $item) {
                continue;
            }

            $item->update([
                'monthly_price' => $data['monthly_price'] === '' ? null : ($data['monthly_price'] ?? null),
                'notes'         => $data['notes'] ?? null,
            ]);
        }

        return redirect()->route('costs.index')->with('success', 'Kosten wurden gespeichert.');
    }

    /**
     * POST /costs
     * Add a free / manual cost line item (e.g. licence, tool, subscription).
     */
    public function store(Request $request): RedirectResponse
    {
        $workspace = app('activeWorkspace');

        $validated = $request->validate([
            'label'         => ['required', 'string', 'max:120'],
            'monthly_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        CostItem::create([
            'workspace_id'  => $workspace->id,
            'user_id'       => $request->user()->id,
            'label'         => $validated['label'],
            'monthly_price' => $validated['monthly_price'] ?? null,
            'notes'         => $validated['notes'] ?? null,
        ]);

        return redirect()->route('costs.index')->with('success', 'Posten wurde hinzugefügt.');
    }

    /**
     * DELETE /costs/{costItem}
     * Remove a manual item. Auto-listed server/domain items cannot be deleted.
     */
    public function destroy(Request $request, CostItem $costItem): RedirectResponse
    {
        $workspace = app('activeWorkspace');

        abort_unless($costItem->workspace_id === $workspace->id, 403);
        abort_unless($costItem->isManual(), 403, 'Server- und Domain-Posten können nicht gelöscht werden.');

        $costItem->delete();

        return redirect()->route('costs.index')->with('success', 'Posten wurde entfernt.');
    }
}
