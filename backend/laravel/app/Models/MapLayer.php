<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapLayer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'layer_type',
        'image_path',
        'uploaded_by_user_id',
        'updated_at',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
