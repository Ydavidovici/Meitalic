<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    // Fields from your order_items migration:
    // id, order_id, product_id, quantity, price, created_at, updated_at
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
    ];

    // Each order item belongs to an order.
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Each order item is linked to a product.
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
