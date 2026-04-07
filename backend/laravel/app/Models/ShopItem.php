<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'code',
        'display_name',
        'cost_json',
        'effect_json',
        'is_active',
    ];

    protected $casts = [
        'cost_json' => 'array',
        'effect_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ShopCategory::class, 'category_id');
    }
}
