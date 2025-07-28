<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'country',
        'password',
        'email_verified',
        'mobile_verified',
        'role',
        'balance',
        'profile_image',
    ];

    protected $casts = [
        'email_verified' => 'boolean',
        'mobile_verified' => 'boolean',
        'balance' => 'decimal:2',
    ];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function apiClients()
    {
        return $this->hasMany(ApiClient::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Custom notifications relationship (renamed to avoid conflict with Notifiable trait)
    public function customNotifications()
    {
        return $this->hasMany(Notification::class);
    }
}
