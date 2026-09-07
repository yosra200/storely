<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryTrackingLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->order_id,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'heading' => $this->heading,
            'speed' => $this->speed,
            'recorded_at' => $this->captured_at?->toIso8601String(),
        ];
    }
}
