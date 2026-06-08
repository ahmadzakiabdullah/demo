<?php

namespace App\Events;

use App\Models\Result;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResultScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Result $result,
        public int $eventId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("events.{$this->eventId}.results"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'result.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'result_id' => $this->result->id,
            'match_id' => $this->result->match_id,
            'status' => $this->result->status->value,
            'data' => $this->result->data,
            'updated_at' => $this->result->updated_at?->toIso8601String(),
        ];
    }
}