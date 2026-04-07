<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NationResource extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'nation_id';
    public $incrementing = false;

    protected $fillable = [
        'nation_id',
        'cow',
        'wood',
        'ore',
        'food',
        'extra_json',
        'updated_at',
    ];

    protected $casts = [
        'extra_json' => 'array',
    ];

    public function nation()
    {
        return $this->belongsTo(Nation::class, 'nation_id');
    }
}
