<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceCheck extends Model
{
    protected $fillable = [
        'item_id',
        'source',
        'title',
        'author',
        'isbn',
        'price',
        'shipping_cost',
        'condition',
        'seller_name',
        'seller_location',
        'description',
        'url',
        'currency',
        'is_available',
        'checked_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'is_available' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getTotalCostAttribute(): float
    {
        return $this->price + $this->shipping_cost;
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('checked_at', 'desc');
    }

    public function scopeByTotalCost($query)
    {
        return $query->orderBy('total_cost', 'asc');
    }
}
