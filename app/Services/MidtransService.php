<?php

namespace App\Services;

use App\Models\Rental;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        /*
        |--------------------------------------------------------------------------
        | [UPDATE ANTI-NYASAR / SERVERLESS VERCEL]
        |--------------------------------------------------------------------------
        | Memaksa Midtrans untuk SELALU mengirim webhook ke URL yang kita tentukan 
        | di file .env (MIDTRANS_NOTIFICATION_URL), terlepas dari apa yang dikonfigurasi 
        | di dalam Dashboard Midtrans. Ini adalah solusi level Enterprise agar 
        | callback di serverless (Vercel) tidak pernah tersesat.
        |--------------------------------------------------------------------------
        */
        $notifUrl = config('services.midtrans.notification_url');
        if ($notifUrl) {
            Config::$overrideNotifUrl = (string) $notifUrl;
        }
    }

    public function createSnapToken(Rental $rental, string $orderId): array
    {
        try {
            $rental->loadMissing('user', 'items');

            $itemDetails = $rental->items->map(function ($item) use ($rental) {
                return [
                    'id' => $item->item_type === 'product'
                        ? 'product-' . ($item->product_id ?? 'na')
                        : 'package-' . ($item->package_id ?? 'na'),
                    'price' => (int) round((float) $item->subtotal),
                    'quantity' => 1,
                    'name' => $item->product_name . ' (' . $rental->total_days . ' hari)',
                ];
            })->values()->all();

            if ((float) $rental->discount_amount > 0) {
                $itemDetails[] = [
                    'id' => 'discount',
                    'price' => -1 * (int) round((float) $rental->discount_amount),
                    'quantity' => 1,
                    'name' => 'Diskon Promo',
                ];
            }

            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) round((float) $rental->grand_total),
                ],
                'customer_details' => [
                    'first_name' => $rental->user->name,
                    'email' => filter_var($rental->user->email, FILTER_VALIDATE_EMAIL) ? $rental->user->email : 'customer' . $rental->user->id . '@hiko.local',
                    'phone' => $rental->user->phone ?? '08000000000',
                ],
                'item_details' => $itemDetails,
                'callbacks' => [
                    'finish' => route('my-rentals.show', $rental->rental_code),
                ],
                'expiry' => [
                    'start_time' => now()->format('Y-m-d H:i:s O'),
                    'unit' => 'hours',
                    'duration' => 24,
                ],
            ];

            $snapToken = Snap::getSnapToken($params);

            return [
                'snap_token' => $snapToken,
                'payment_url' => null,
            ];
        } catch (\Throwable $e) {
            Log::channel('app_error')->error('MidtransService::createSnapToken failed', [
                'rental_id' => $rental->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
