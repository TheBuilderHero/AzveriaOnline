<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopCategory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'display_name',
    ];

    public function items()
    {
        return $this->hasMany(ShopItem::class, 'category_id');
    }
}
