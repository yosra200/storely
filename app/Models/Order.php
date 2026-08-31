<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_id',
        'created_by',
        'delivery_id',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'delivery_fee',
        'total_amount',
        'notes',
        'location_expires_at',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
