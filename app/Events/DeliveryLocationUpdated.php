<?php

namespace App\Events;

use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public float $latitude,
        public float $longitude,
        public ?float $heading = null,
        public ?float $speed = null,
        public ?CarbonInterface $recordedAt = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('order.' . $this->order->id);
    }

    public function broadcastAs(): string
    {
        return 'delivery.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'heading' => $this->heading,
            'speed' => $this->speed,
            'recorded_at' => ($this->recordedAt ?? now())->toIso8601String(),
        ];
    }
}
