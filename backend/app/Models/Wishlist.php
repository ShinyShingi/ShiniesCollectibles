<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_public',
        'share_token',
        'slug',
        'settings',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'settings' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($wishlist) {
            if (empty($wishlist->slug)) {
                $wishlist->slug = Str::slug($wishlist->name) . '-' . Str::random(6);
            }
            
            if ($wishlist->is_public && empty($wishlist->share_token)) {
                $wishlist->share_token = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'wishlist_items')
            ->withPivot(['target_price', 'notes', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority', 'desc');
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    // public function getRouteKeyName(): string
    // {
    //     return 'slug';
    // }

    public function getPublicUrlAttribute(): ?string
    {
        if (!$this->is_public || !$this->share_token) {
            return null;
        }
        
        return route('wishlists.public', $this->share_token);
    }

    public function generateShareToken(): void
    {
        $this->share_token = Str::random(32);
        $this->save();
    }

    public function togglePublic(): void
    {
        $this->is_public = !$this->is_public;
        
        if ($this->is_public && !$this->share_token) {
            $this->generateShareToken();
        }
        
        $this->save();
    }
}
