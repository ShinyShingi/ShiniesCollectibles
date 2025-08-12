<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'media_type',
        'title',
        'year',
        'cover_url',
        'owned',
        'condition',
        'purchase_price',
        'purchase_date',
        'notes',
    ];

    protected $casts = [
        'owned' => 'boolean',
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function identifiers()
    {
        return $this->hasMany(Identifier::class);
    }

    public function contributors()
    {
        return $this->belongsToMany(Contributor::class, 'item_contributors')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'collection_items')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'item_tags')
            ->withTimestamps();
    }

    public function itemContributors()
    {
        return $this->hasMany(ItemContributor::class);
    }

    public function scopeBooks($query)
    {
        return $query->where('media_type', 'book');
    }

    public function scopeMusic($query)
    {
        return $query->where('media_type', 'music');
    }

    public function scopeOwned($query)
    {
        return $query->where('owned', true);
    }

    public function priceChecks()
    {
        return $this->hasMany(PriceCheck::class);
    }

    public function latestPriceChecks()
    {
        return $this->hasMany(PriceCheck::class)
            ->latest('checked_at')
            ->available();
    }

    public function getIsbnAttribute()
    {
        return $this->identifiers()
            ->where('type', 'isbn')
            ->first()?->value;
    }
}