<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Events\NewRentalNotification;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_code',
        'user_id',
        'status',
        'rental_start',
        'rental_end',
        'total_days',
        'subtotal',
        'total_rental',
        'discount_amount',
        'grand_total',
        'items_count',
        'notes',
        'picked_up_at',
        'returned_at',
        'late_days',
        'late_penalty',
        'damage_penalty',
        'total_penalty',
        'penalty_status',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (Rental $rental) {
            // Tembakkan event real-time ke admin ketika ada rental baru
            event(new NewRentalNotification($rental));
        });
    }

    protected function casts(): array
    {
        return [
            'rental_start' => 'date',
            'rental_end' => 'date',
            'picked_up_at' => 'datetime',
            'returned_at' => 'datetime',
            'total_days' => 'integer',
            'late_days' => 'integer',
            'subtotal' => 'decimal:2',
            'total_rental' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'late_penalty' => 'decimal:2',
            'damage_penalty' => 'decimal:2',
            'total_penalty' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function items(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

}
