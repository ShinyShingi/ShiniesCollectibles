<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Identifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'type',
        'value',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}