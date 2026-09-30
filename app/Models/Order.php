<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_phone',
        'sales_id',
        'supervisor_id',
        'packing_id',
        'created_by',
        'delivery_id',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_heading',
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

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function packing()
    {
        return $this->belongsTo(User::class, 'packing_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
