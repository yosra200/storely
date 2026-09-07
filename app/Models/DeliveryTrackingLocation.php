<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryTrackingLocation extends Model
{
    protected $fillable = [
        'order_id',
        'delivery_id',
        'latitude',
        'longitude',
        'heading',
        'speed',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'heading' => 'float',
            'speed' => 'float',
            'captured_at' => 'datetime',
        ];
    }
}
