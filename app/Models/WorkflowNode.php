<?php

namespace App\Models;

use App\Enums\WorkflowNodeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowNode extends Model
{
    protected $fillable = [
        'workspace_id',
        'type',
        'label',
        'position_x',
        'position_y',
        'meta',
    ];

    protected $casts = [
        'type'       => WorkflowNodeType::class,
        'position_x' => 'integer',
        'position_y' => 'integer',
        'meta'       => 'array',
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<WorkflowEdge, $this> */
    public function outgoingEdges(): HasMany
    {
        return $this->hasMany(WorkflowEdge::class, 'from_node_id');
    }

    /** @return HasMany<WorkflowEdge, $this> */
    public function incomingEdges(): HasMany
    {
        return $this->hasMany(WorkflowEdge::class, 'to_node_id');
    }
}
