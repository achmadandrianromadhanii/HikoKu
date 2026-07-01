<?php

namespace App\Events;

use App\Models\Rental;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewRentalNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rental;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Rental $rental)
    {
        $this->rental = $rental;
        // Kita juga bisa memuat relasi dasar jika diperlukan
        $this->rental->loadMissing('user');
        $this->message = 'Ada pesanan rental baru: ' . $rental->rental_code;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('admin-notifications'),
        ];
    }

    /**
     * Menentukan nama event yang akan didengarkan di frontend (Echo)
     */
    public function broadcastAs(): string
    {
        return 'NewRentalCreated';
    }
}
