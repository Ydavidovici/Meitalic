<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // Fields from your payments migration:
    // id, order_id, stripe_payment_id, amount, status, created_at, updated_at
    protected $fillable = [
        'order_id',
        'stripe_payment_id',
        'amount',
        'status',
    ];

    // A payment is linked to a specific order.
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
