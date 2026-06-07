<?php

namespace App\Models;

use App\Enums\WorkspaceType;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'type', 'color', 'settings'];

    protected function casts(): array
    {
        return [
            'type'     => WorkspaceType::class,
            'settings' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Server, $this> */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /** @return HasMany<CostItem, $this> */
    public function costItems(): HasMany
    {
        return $this->hasMany(CostItem::class);
    }

    /** @return HasMany<WorkflowNode, $this> */
    public function workflowNodes(): HasMany
    {
        return $this->hasMany(WorkflowNode::class);
    }

    /** @return HasMany<WorkflowEdge, $this> */
    public function workflowEdges(): HasMany
    {
        return $this->hasMany(WorkflowEdge::class);
    }
}
