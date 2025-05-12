<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Mass assignable */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_subscribed',
    ];

    /** Hidden for serialization */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Casts */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin'          => 'boolean',
        'is_subscribed'     => 'boolean',
        'subscribed_at'     => 'datetime',
        'password'          => 'hashed',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
