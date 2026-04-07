<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['password'] = $value;
            return;
        }

        $info = password_get_info((string) $value);
        $this->attributes['password'] = $info['algo'] ? (string) $value : Hash::make((string) $value);
    }

    public function nation()
    {
        return $this->hasOne(Nation::class, 'owner_user_id');
    }

    public function chatMemberships()
    {
        return $this->belongsToMany(Chat::class, 'chat_members', 'user_id', 'chat_id');
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class, 'user_id');
    }
}
