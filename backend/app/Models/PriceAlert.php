<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAlert extends Model
{
    protected $fillable = [
        'user_id',
        'item_id',
        'price_check_id',
        'target_price',
        'triggered_price',
        'source',
        'triggered_at',
        'notified_at',
        'is_read',
    ];

    protected $casts = [
        'target_price' => 'decimal:2',
        'triggered_price' => 'decimal:2',
        'triggered_at' => 'datetime',
        'notified_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function priceCheck(): BelongsTo
    {
        return $this->belongsTo(PriceCheck::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('triggered_at', '>=', now()->subDays($days));
    }

    public function scopeNotNotified($query)
    {
        return $query->whereNull('notified_at');
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function markAsNotified(): void
    {
        $this->update(['notified_at' => now()]);
    }

    public function getSavingsAttribute(): float
    {
        return $this->target_price - $this->triggered_price;
    }

    public function getSavingsPercentageAttribute(): float
    {
        if ($this->target_price <= 0) {
            return 0;
        }

        return ($this->savings / $this->target_price) * 100;
    }
}
