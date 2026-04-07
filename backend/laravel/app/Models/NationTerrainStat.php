<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NationTerrainStat extends Model
{
    public $timestamps = false;
    protected $table = 'nation_terrain_stats';
    protected $primaryKey = 'nation_id';
    public $incrementing = false;

    protected $fillable = [
        'nation_id',
        'grassland_pct',
        'mountain_pct',
        'freshwater_pct',
        'hills_pct',
        'desert_pct',
        'square_miles_json',
        'updated_at',
    ];

    protected $casts = [
        'square_miles_json' => 'array',
    ];

    public function nation()
    {
        return $this->belongsTo(Nation::class, 'nation_id');
    }
}
