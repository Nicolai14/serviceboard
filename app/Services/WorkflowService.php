<?php

namespace App\Services;

use App\Models\Workspace;
use App\Models\WorkflowEdge;
use App\Models\WorkflowNode;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    /**
     * Load the full workflow graph of a workspace as plain arrays, ready to
     * hand to the canvas frontend.
     *
     * @return array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}
     */
    public function graph(Workspace $workspace): array
    {
        $nodes = $workspace->workflowNodes()
            ->orderBy('id')
            ->get()
            ->map(fn (WorkflowNode $n) => [
                'id'    => $n->id,
                'type'  => $n->type->value,
                'label' => $n->label ?? '',
                'x'     => $n->position_x,
                'y'     => $n->position_y,
                'meta'  => $n->meta ?? [],
            ])
            ->all();

        $edges = $workspace->workflowEdges()
            ->orderBy('id')
            ->get()
            ->map(fn (WorkflowEdge $e) => [
                'id'    => $e->id,
                'from'  => $e->from_node_id,
                'to'    => $e->to_node_id,
                'label' => $e->label ?? '',
            ])
            ->all();

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * Persist the full graph for a workspace. The canvas sends the complete
     * state on every save, so we replace the stored graph wholesale inside a
     * transaction. Edges reference nodes by the client-side id present in the
     * payload; those ids are remapped to the freshly inserted primary keys.
     *
     * @param  array<int, array{type: string, label?: string|null, x?: int|float, y?: int|float, meta?: array<string, mixed>|null, id?: int|string}>  $nodes
     * @param  array<int, array{from: int|string, to: int|string, label?: string|null}>  $edges
     */
    public function save(Workspace $workspace, array $nodes, array $edges): void
    {
        DB::transaction(function () use ($workspace, $nodes, $edges) {
            // Wipe the existing graph (edges first — they reference nodes).
            $workspace->workflowEdges()->delete();
            $workspace->workflowNodes()->delete();

            // Recreate nodes, mapping each client id to its new primary key.
            $idMap = [];

            foreach ($nodes as $node) {
                $created = $workspace->workflowNodes()->create([
                    'type'       => $node['type'],
                    'label'      => $node['label'] ?? null,
                    'position_x' => (int) round($node['x'] ?? 0),
                    'position_y' => (int) round($node['y'] ?? 0),
                    'meta'       => $node['meta'] ?? null,
                ]);

                if (isset($node['id'])) {
                    $idMap[(string) $node['id']] = $created->id;
                }
            }

            // Recreate edges, skipping any whose endpoints are unknown.
            foreach ($edges as $edge) {
                $from = $idMap[(string) $edge['from']] ?? null;
                $to   = $idMap[(string) $edge['to']] ?? null;

                if ($from === null || $to === null || $from === $to) {
                    continue;
                }

                $workspace->workflowEdges()->create([
                    'from_node_id' => $from,
                    'to_node_id'   => $to,
                    'label'        => $edge['label'] ?? null,
                ]);
            }
        });
    }
}
