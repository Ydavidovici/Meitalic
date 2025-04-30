<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'discount',
        'max_uses',
        'used_count',
        'expires_at',
        'active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'active'     => 'boolean',
    ];
}
