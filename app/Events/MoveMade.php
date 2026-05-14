<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MoveMade implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow
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
        return [new PresenceChannel('chess.' . $this->roomId)];
    }

    // THÊM HÀM NÀY ĐỂ ÉP TÊN SỰ KIỆN:
    public function broadcastAs()
    {
        return 'MoveMade';
    }
}
