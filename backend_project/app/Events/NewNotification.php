<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotification implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.' . $this->notification->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new-notification';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->notification->id,
            'title'          => $this->notification->title,
            'body'           => $this->notification->body,
            'type'           => $this->notification->type,
            'reference_type' => $this->notification->reference_type,
            'reference_id'   => $this->notification->reference_id,
            'is_read'        => (bool) $this->notification->is_read,
            'created_at'     => $this->notification->created_at?->toDateTimeString(),
        ];
    }
}