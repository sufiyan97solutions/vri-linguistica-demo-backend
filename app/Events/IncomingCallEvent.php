<?php

namespace App\Events;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class IncomingCallEvent implements ShouldBroadcastNow
{
    public $callData;
    protected $userId;

    public function __construct(array $callData, $userId)
    {
        $this->callData = $callData;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new Channel("interpreter.{$this->userId}");
        // return new PrivateChannel("interpreter.{$this->userId}");
    }

    public function broadcastAs()
    {
        return 'incoming-call';
    }
}
