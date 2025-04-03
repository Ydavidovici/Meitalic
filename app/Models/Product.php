<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Updated fillable to include the new 'brand' field
    protected $fillable = [
        'name',
        'brand',    
        'description',
        'price',
        'image',
        'inventory',
    ];

    // A product can appear in many order items.
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
