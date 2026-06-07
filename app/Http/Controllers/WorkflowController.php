<?php

namespace App\Http\Controllers;

use App\Enums\WorkflowNodeType;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    public function __construct(private readonly WorkflowService $workflows) {}

    /**
     * GET /workflow
     * Visual building-block canvas for the active workspace (one per project).
     */
    public function index(): View
    {
        $workspace = app('activeWorkspace');

        return view('workflow.index', [
            'workspace' => $workspace,
            'graph'     => $this->workflows->graph($workspace),
            'palette'   => WorkflowNodeType::palette(),
        ]);
    }

    /**
     * PUT /workflow
     * Save the full graph (nodes + connections) in one request.
     */
    public function update(Request $request): JsonResponse
    {
        $workspace = app('activeWorkspace');

        $validated = $request->validate([
            'nodes'          => ['present', 'array'],
            'nodes.*.id'     => ['required'],
            'nodes.*.type'   => ['required', Rule::enum(WorkflowNodeType::class)],
            'nodes.*.label'  => ['nullable', 'string', 'max:120'],
            'nodes.*.x'      => ['required', 'numeric'],
            'nodes.*.y'      => ['required', 'numeric'],
            'nodes.*.meta'   => ['nullable', 'array'],

            'edges'          => ['present', 'array'],
            'edges.*.from'   => ['required'],
            'edges.*.to'     => ['required'],
            'edges.*.label'  => ['nullable', 'string', 'max:80'],
        ]);

        $this->workflows->save($workspace, $validated['nodes'], $validated['edges']);

        return response()->json([
            'status' => 'ok',
            'graph'  => $this->workflows->graph($workspace),
        ]);
    }
}
