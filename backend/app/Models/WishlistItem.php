<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    protected $fillable = [
        'wishlist_id',
        'item_id',
        'target_price',
        'notes',
        'priority',
    ];

    protected $casts = [
        'target_price' => 'decimal:2',
        'priority' => 'integer',
    ];

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function scopeWithTargetPrice($query)
    {
        return $query->whereNotNull('target_price');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 4);
    }

    public function getCurrentLowestPriceAttribute()
    {
        return $this->item->latestPriceChecks()
            ->available()
            ->min('total_cost');
    }

    public function getIsBelowTargetAttribute(): bool
    {
        if (!$this->target_price) {
            return false;
        }

        $lowestPrice = $this->getCurrentLowestPriceAttribute();
        return $lowestPrice && $lowestPrice <= $this->target_price;
    }
}
