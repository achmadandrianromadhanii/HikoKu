<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_id',
        'payment_code',
        'method',
        'gateway_transaction_id',
        'snap_token',
        'payment_url',
        'amount',
        'status',
        'paid_at',
        'confirmed_by',
        'notes',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function (Payment $payment) {
            // Jika status berubah menjadi 'paid' atau jika dibuat pertama kali langsung 'paid'
            if ($payment->wasChanged('status') && $payment->status === 'paid') {
                event(new \App\Events\PaymentSuccessNotification($payment));
            } elseif ($payment->wasRecentlyCreated && $payment->status === 'paid') {
                event(new \App\Events\PaymentSuccessNotification($payment));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
