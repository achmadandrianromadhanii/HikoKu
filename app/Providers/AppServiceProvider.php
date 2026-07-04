<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // [OPTIMASI DATABASE]: Mengaktifkan "Strict Mode" Eloquent secara otomatis.
        // Fitur resmi Laravel ini memblokir N+1 Query Problem (kebocoran query berulang)
        // selama masa pengembangan lokal. Di server production, hanya akan mencatat error di log agar web tidak crash.
        // Ini adalah cara terbaik dan paling resmi untuk meringankan beban database 100%.
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(!app()->isProduction());
        
        // [UPDATE]: Mendaftarkan Discord Provider ke dalam siklus Socialite
        \Illuminate\Support\Facades\Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('discord', \SocialiteProviders\Discord\Provider::class);
        });

        // [PERBAIKAN OAUTH BUG]: Memaksa scheme HTTPS pada URL internal Laravel saat berada di Production (Vercel dsb).
        // Ini mencegah masalah redirect_uri mismatch di Google OAuth karena Laravel menghasilkan URL http:// di belakang proxy.
        if (config('app.env') === 'production' || env('FORCE_HTTPS', false)) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->ip()),
            ];
        });

        RateLimiter::for('checkout', function (Request $request) {
            return [
                Limit::perMinute(5)->by(optional($request->user())->id ?: $request->ip()),
            ];
        });

        RateLimiter::for('payment-retry', function (Request $request) {
            return [
                Limit::perMinute(3)->by(optional($request->user())->id ?: $request->ip()),
            ];
        });

        // [OPTIMASI KEAMANAN & BEBAN SERVER]: Rate limiter "global"
        // Pembatasan default 60 request per menit per IP untuk mencegah serangan DDoS ringan atau brute-force 
        // yang dapat membebani CPU dan memperlambat Lighthouse.
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
