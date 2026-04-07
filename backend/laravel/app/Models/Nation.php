<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nation extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_user_id',
        'name',
        'is_placeholder',
        'leader_name',
        'alliance_name',
        'about_text',
    ];

    protected $casts = [
        'is_placeholder' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function resources()
    {
        return $this->hasOne(NationResource::class, 'nation_id');
    }

    public function terrainStats()
    {
        return $this->hasOne(NationTerrainStat::class, 'nation_id');
    }
}
