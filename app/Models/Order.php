<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Assuming your orders table has these columns (user_id, shipping_address, total, status, etc.)
    protected $fillable = [
        'user_id',
        'shipping_address',
        'shipping_fee',
        'total',
        'status',
        'email',
        'phone',
        'meta'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // An order has many order items.
    public function Items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // An order may have one payment record.
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
