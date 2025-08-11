<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemContributor extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'contributor_id',
        'role',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function contributor()
    {
        return $this->belongsTo(Contributor::class);
    }
}