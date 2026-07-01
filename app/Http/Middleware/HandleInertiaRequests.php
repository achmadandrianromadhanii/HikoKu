<?php

namespace App\Http\Middleware;

use App\Models\CartItem;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * Bagi data global untuk semua halaman Inertia.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'role' => $user->getRoleNames()->first(),
                    'email_verified_at' => $user->email_verified_at,
                    'is_social_login' => (bool) ($user->google_id || $user->github_id || $user->discord_id),
                    'has_password' => $user->password !== null,
                ] : null,
            ],

            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'wishlist' => fn() => $request->session()->get('wishlist'),
            ],

            // [KOMENTAR PENJELASAN]: Mengirimkan daftar ID produk yang di-wishlist oleh pengguna ke seluruh halaman (Persisten).
            // Data ini digunakan oleh Pinia store agar saat refresh, status tombol love tetap merah tanpa perlu delay.
            'wishlist' => [
                'product_ids' => fn() => $user
                    ? \App\Models\Wishlist::query()
                        ->where('user_id', $user->id)
                        ->pluck('product_id')
                        ->toArray()
                    : [],
            ],



            'cart' => [
                'count' => fn() => $user
                    ? CartItem::query()
                    ->where('user_id', $user->id)
                    ->where('expires_at', '>', now())
                    ->count()
                    : 0,
            ],

            'settings' => [
                'public' => fn() => [
                    'app_name' => 'Hiko',
                    'app_tagline' => 'Outdoor Equipment Rental',
                    'store_address' => 'Jl. Merdeka No.123, Bandung',
                    'store_phone' => '081234567890',
                    'store_email' => 'hello@hiko.com',
                    'wa_number' => '081234567890',
                    'wa_greeting' => 'Halo, saya ingin bertanya tentang...',
                    'instagram_url' => 'https://instagram.com/hiko',
                    'opening_hours' => 'Senin - Minggu: 08:00 - 21:00',
                ],
            ],

            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],

            // [NEW]: Melempar Site Key Turnstile secara publik ke Frontend
            'turnstile_site_key' => config('services.turnstile.site_key'),
        ];
    }
}
