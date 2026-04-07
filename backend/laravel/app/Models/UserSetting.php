<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'theme',
        'color_blind_mode',
        'dog_bark_enabled',
        'extra_json',
        'updated_at',
    ];

    protected $casts = [
        'dog_bark_enabled' => 'boolean',
        'extra_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
