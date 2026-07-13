<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaroMoveMade implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;

    public $move;

    public function __construct($roomId, $move)
    {
        $this->roomId = $roomId;
        $this->move = $move;
    }

    public function broadcastOn(): array
    {
        return [new PresenceChannel('caro.'.$this->roomId)];
    }

    public function broadcastAs()
    {
        return 'CaroMoveMade';
    }
}
