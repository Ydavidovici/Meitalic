<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'user_id',
        'product_id',
        'rating',
        'body',
        'status',
    ];

    /**
     * The order‐item this review belongs to.
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * The user who left the review.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The product being reviewed.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
