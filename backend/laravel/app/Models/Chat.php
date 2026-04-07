<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'created_by_user_id',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'chat_members', 'chat_id', 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'chat_id');
    }
}
