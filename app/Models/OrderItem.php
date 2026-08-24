<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Order;

class OrderItem extends Model
{
    protected $fillable = ['price', 'quantity', 'product_name', 'order_id'];



    public function order()
    {
        $this->belongsTo(Order::class, 'order_id');
    }
}
