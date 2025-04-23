<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_amount',
        'discount_percent',
        'max_uses',
        'used_count',
        'expires_at',
    ];

    public function isValid()
    {
        return (!$this->expires_at || $this->expires_at->isFuture())
            && (!$this->max_uses || $this->used_count < $this->max_uses);
    }
}
