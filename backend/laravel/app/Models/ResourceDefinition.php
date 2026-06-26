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
        'group_order',
        'order',
        'meta',
    ];

    protected $casts = [
        'group_order' => 'integer',
        'meta' => 'array',
    ];
}
