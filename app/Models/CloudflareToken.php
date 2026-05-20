<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudflareToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'api_token',
        'account_id',
        'account_name',
        'status',
        'error_message',
        'last_verified_at',
    ];

    protected $casts = [
        'api_token'        => 'encrypted',
        'last_verified_at' => 'datetime',
    ];

    protected $hidden = ['api_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(CloudflareZone::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function markError(string $message): void
    {
        $this->update(['status' => 'error', 'error_message' => $message]);
    }

    public function markActive(?string $accountId = null, ?string $accountName = null): void
    {
        $this->update([
            'status'           => 'active',
            'error_message'    => null,
            'account_id'       => $accountId ?? $this->account_id,
            'account_name'     => $accountName ?? $this->account_name,
            'last_verified_at' => now(),
        ]);
    }
}
