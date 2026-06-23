<?php

namespace App\Http\Controllers;

use App\Models\CostItem;
use App\Services\CostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'is_recurring'  => ['boolean'],
            'receipt'       => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')
                ->store("receipts/{$workspace->id}", 'local');
        }

        CostItem::create([
            'workspace_id'  => $workspace->id,
            'user_id'       => $request->user()->id,
            'label'         => $validated['label'],
            'monthly_price' => $validated['monthly_price'] ?? null,
            'notes'         => $validated['notes'] ?? null,
            'is_recurring'  => $validated['is_recurring'] ?? true,
            'receipt_path'  => $receiptPath,
        ]);

        return redirect()->route('costs.index')->with('success', 'Posten wurde hinzugefügt.');
    }

    /**
     * GET /costs/{costItem}/receipt
     * Stream the stored receipt file to the authenticated workspace member.
     */
    public function receipt(Request $request, CostItem $costItem): StreamedResponse
    {
        $workspace = app('activeWorkspace');

        abort_unless($costItem->workspace_id === $workspace->id, 403);
        abort_unless(
            $costItem->receipt_path && Storage::disk('local')->exists($costItem->receipt_path),
            404,
            'Rechnung nicht gefunden.'
        );

        return Storage::disk('local')->download($costItem->receipt_path);
    }

    /**
     * DELETE /costs/{costItem}/resource
     * Delete the underlying server or domain (and its cost row) from the system.
     */
    public function destroyResource(Request $request, CostItem $costItem): RedirectResponse
    {
        $workspace = app('activeWorkspace');

        abort_unless($costItem->workspace_id === $workspace->id, 403);
        abort_unless(! $costItem->isManual(), 403);

        $costable = $costItem->costable;
        abort_unless($costable !== null, 404);

        $name = $costItem->displayName();
        $costable->delete();

        return redirect()->route('costs.index')->with('success', "„{$name}" wurde entfernt.");
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

        if ($costItem->receipt_path) {
            Storage::disk('local')->delete($costItem->receipt_path);
        }

        $costItem->delete();

        return redirect()->route('costs.index')->with('success', 'Posten wurde entfernt.');
    }
}
