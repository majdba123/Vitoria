<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationSent implements ShouldBroadcast, ShouldDispatchAfterCommit, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array<int>  $recipientUserIds  Explicit recipient user IDs; each gets their own private channel.
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $body,
        public string $type,
        public array $recipientUserIds = [],
        public ?string $actionType = null,
        public ?int $actionId = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Never a public channel: every notification, including marketing
        // ones, is delivered only to its explicit recipients' private channels.
        $channels = [];
        foreach ($this->recipientUserIds as $userId) {
            $channels[] = new PrivateChannel('App.Models.User.'.$userId);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'AdminNotificationSent';
    }

    /**
     * Data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'action_type' => $this->actionType,
            'action_id' => $this->actionId,
        ];
    }
}
