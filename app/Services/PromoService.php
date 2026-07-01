<?php

namespace App\Services;

use App\Models\Promo;
use App\Models\PromoUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromoService
{
    public function validatePromo(string $code, int $userId, float $subtotal): array
    {
        return DB::transaction(function () use ($code, $userId, $subtotal) {
            $promo = Promo::query()
                ->where('code', $code)
                ->lockForUpdate()
                ->first();

            if (! $promo) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Kode promo tidak ditemukan.',
                ]);
            }

            if (! $promo->is_active) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Promo tidak aktif.',
                ]);
            }

            if ($promo->valid_from && now()->lt($promo->valid_from)) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Promo belum berlaku.',
                ]);
            }

            if ($promo->valid_until && now()->gt($promo->valid_until)) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Promo sudah berakhir.',
                ]);
            }

            if (! is_null($promo->usage_limit) && $promo->used_count >= $promo->usage_limit) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Kuota promo habis.',
                ]);
            }

            if (! is_null($promo->limit_per_user)) {
                $usedByUser = PromoUsage::query()
                    ->where('promo_id', $promo->id)
                    ->where('user_id', $userId)
                    ->count();

                if ($usedByUser >= $promo->limit_per_user) {
                    throw ValidationException::withMessages([
                        'promo_code' => 'Promo sudah pernah kamu gunakan.',
                    ]);
                }
            }

            if ($subtotal < (float) $promo->min_rental) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Subtotal belum memenuhi minimum promo.',
                ]);
            }

            $discount = $promo->type === 'percentage'
                ? $subtotal * ((float) $promo->value / 100)
                : (float) $promo->value;

            if (! is_null($promo->max_discount)) {
                $discount = min($discount, (float) $promo->max_discount);
            }

            $discount = max(0, $discount);

            return [
                'promo' => $promo,
                'discount' => round($discount, 2),
            ];
        });
    }

    public function applyUsage(Promo $promo, int $userId, int $rentalId): void
    {
        $promo->increment('used_count');

        PromoUsage::create([
            'promo_id' => $promo->id,
            'user_id' => $userId,
            'rental_id' => $rentalId,
            'used_at' => now(),
        ]);
    }
}
