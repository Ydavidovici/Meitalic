<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'label_id',
        'tracking_number',
        'carrier_code',
        'service_code',
        'shipment_cost',
        'other_cost',
        'label_url',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}