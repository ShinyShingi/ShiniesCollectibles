<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Contributor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contributor) {
            if (empty($contributor->slug)) {
                $contributor->slug = Str::slug($contributor->name);
            }
        });

        static::updating(function ($contributor) {
            if ($contributor->isDirty('name') && empty($contributor->slug)) {
                $contributor->slug = Str::slug($contributor->name);
            }
        });
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_contributors')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function itemContributors()
    {
        return $this->hasMany(ItemContributor::class);
    }
}