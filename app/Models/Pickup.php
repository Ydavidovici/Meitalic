<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pickup extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_date',
        'confirmation_number',
        'payload',
    ];

    protected $casts = [
        'pickup_date'         => 'date',
        'payload'             => 'array',
    ];
}