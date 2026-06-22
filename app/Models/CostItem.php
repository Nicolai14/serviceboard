<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CostItem extends Model
{
    protected $fillable = [
        'workspace_id',
        'user_id',
        'costable_type',
        'costable_id',
        'label',
        'monthly_price',
        'currency',
        'notes',
        'is_recurring',
        'receipt_path',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'is_recurring'  => 'boolean',
    ];

    /** @return MorphTo<Model, $this> */
    public function costable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isManual(): bool
    {
        return $this->costable_type === null;
    }

    /**
     * Human-readable name: derived from the linked resource, or the manual label.
     */
    public function displayName(): string
    {
        $costable = $this->costable;

        if ($costable instanceof Server || $costable instanceof CloudflareZone) {
            return $costable->name;
        }

        if ($this->isManual()) {
            return $this->label ?: 'Unbenannter Posten';
        }

        // Linked item whose resource was deleted.
        return match ($this->costable_type) {
            Server::class         => 'Gelöschter Server',
            CloudflareZone::class => 'Gelöschte Domain',
            default               => $this->label ?: '—',
        };
    }

    /**
     * Category key used for grouping in the UI.
     */
    public function category(): string
    {
        return match ($this->costable_type) {
            Server::class         => 'server',
            CloudflareZone::class => 'domain',
            default               => 'manual',
        };
    }
}
