<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
        $this->payment->loadMissing(['rental', 'rental.user']);
        $this->message = 'Pembayaran berhasil untuk transaksi: ' . ($payment->rental->rental_code ?? 'Unknown');
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
        return 'PaymentSuccess';
    }
}
