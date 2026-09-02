<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryTrackingStarted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel(
            'order.' . $this->order->id
        );
    }

    public function broadcastAs(): string
    {
        return 'tracking.started';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'delivery_id' => $this->order->delivery_id,
            'status' => $this->order->status,
            'tracking' => true,
        ];
    }
}
