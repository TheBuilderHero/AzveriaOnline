<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceDefinition extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'type',
        'group',
        'order',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
