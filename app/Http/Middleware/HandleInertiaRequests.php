<?php

namespace App\Http\Middleware;

use App\Models\CartItem;
use Illuminate\Support\Facades\Cache;

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

            // [OPTIMASI VERCEL/LATENCY]: Membungkus query menggunakan Cache::remember.
            // Di Vercel, mengeksekusi query database di setiap perpindahan halaman akan membuat web sangat lambat (Lagging/Lemot).
            // Cache ini akan disimpan di memori selama 24 jam dan hanya di-query ulang jika Cache dihapus saat ada perubahan.
            'wishlist' => [
                'product_ids' => fn() => $user
                    ? Cache::remember('wishlist_user_' . $user->id, now()->addHours(24), function () use ($user) {
                        return \App\Models\Wishlist::query()
                            ->where('user_id', $user->id)
                            ->pluck('product_id')
                            ->toArray();
                    })
                    : [],
            ],

            'cart' => [
                'count' => fn() => $user
                    ? Cache::remember('cart_count_user_' . $user->id, now()->addHours(24), function () use ($user) {
                        return CartItem::query()
                            ->where('user_id', $user->id)
                            ->where('expires_at', '>', now())
                            ->count();
                    })
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

            // [OPTIMASI VERCEL/LATENCY]: Ziggy Routes TELAH DIHAPUS DARI SHARE INERTIA!
            // Mengirim data ziggy di SETIAP request Inertia membuat payload JSON sangat besar (ratusan KB)
            // yang menyebabkan pindah halaman menjadi "Lama dan Lemot". 
            // Karena kita sudah menggunakan @routes di app.blade.php, ZiggyVue akan otomatis mengambil dari window.Ziggy
            // Hasilnya: Pindah halaman akan INSTAN dan ukuran request turun drastis!
            
            // [NEW]: Melempar Site Key Turnstile secara publik ke Frontend
            'turnstile_site_key' => config('services.turnstile.site_key'),
        ];
    }
}
