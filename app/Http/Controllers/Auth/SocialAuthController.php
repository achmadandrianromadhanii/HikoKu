<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect ke Social Provider (Google/GitHub).
     */
    public function redirect($provider)
    {
        // [UPDATE]: Menerapkan mode stateless() secara resmi sesuai dokumentasi Laravel.
        // Fungsi stateless() mematikan verifikasi State berbasis Session.
        // Ini SANGAT WAJIB untuk serverless hosting (Vercel/Lambda) karena Cookie dan Session
        // sering terhapus otomatis oleh server saat melakukan redirect lintas domain (Cross-Site).
        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * Callback dari Social Provider.
     */
    public function callback($provider)
    {
        /*
         * 1. PENCEGAHAN BATAL LOGIN (User Cancel)
         * Jika pengguna menekan tombol batal (cancel) saat di halaman Google/GitHub,
         * provider akan mengembalikan parameter 'error'. Kita tangkap ini dan 
         * kembalikan pengguna ke halaman login dengan aman tanpa membebani server.
         */
        if (request()->has('error')) {
            return redirect()->route('login')->with('error', 'Otorisasi ' . ucfirst($provider) . ' dibatalkan.');
        }

        try {
            // [UPDATE]: Menerapkan mode stateless() pada tahap callback untuk menerima balasan 
            // dari Google/GitHub tanpa mencocokkan Session State (karena Session di Vercel rentan hilang).
            // Ini akan menyelesaikan masalah InvalidStateException dan otentikasi macet.
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Login ' . ucfirst($provider) . ' gagal diproses. Silakan coba lagi.');
        }

        /*
         * 2. KEAMANAN EMAIL KOSONG
         * Terkadang pengguna GitHub menyembunyikan email mereka (private email).
         * Sistem kita membutuhkan email untuk mencegah akun ganda. Jika email kosong, kita tolak.
         */
        if (! $socialUser->getEmail()) {
            return redirect()->route('login')->with('error', 'Email tidak ditemukan dari akun ' . ucfirst($provider) . '. Pastikan email Anda disetel publik.');
        }

        // [UPDATE]: Ambil avatar asli dari sosmed. Jika Google, ubah s96 (kecil) jadi s400 (HD).
        $avatarUrl = $socialUser->getAvatar();
        if ($provider === 'google' && $avatarUrl) {
            $avatarUrl = str_replace('=s96-c', '=s400-c', $avatarUrl);
        }

        /*
         * 3. PENCEGAHAN AKUN GANDA (Double Account Prevention)
         * Alih-alih langsung membuat akun baru, kita cari dulu apakah email ini 
         * sudah pernah terdaftar di sistem kita (baik lewat pendaftaran manual maupun provider lain).
         */
        $user = User::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            // Jika benar-benar tidak ada, barulah kita buatkan akun baru (Otomatis terverifikasi).
            $user = User::create([
                'name' => $socialUser->getName() ?: 'User ' . ucfirst($provider),
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(24)), // Password acak kuat karena login pakai provider
                'email_verified_at' => now(), // Anggap sudah terverifikasi karena dari Google/GitHub
                'avatar' => $avatarUrl, // Simpan otomatis foto dari sosmed
                'is_active' => true,
            ]);
        } else {
            // Jika user sudah ada, sinkronkan/update fotonya jika sebelumnya kosong
            if (!$user->avatar && $avatarUrl) {
                $user->update(['avatar' => $avatarUrl]);
            }
        }

        /*
         * 4. SINKRONISASI AKUN (Account Linking)
         */
        if ($provider === 'google') {
            $user->update(['google_id' => $socialUser->getId()]);
        } elseif ($provider === 'github') {
            $user->update(['github_id' => $socialUser->getId()]);
        } elseif ($provider === 'discord') {
            $user->update(['discord_id' => $socialUser->getId()]);
        }

        /*
         * 5. OTORISASI PERAN (Role Authorization)
         * Pastikan akun yang masuk via Google/GitHub akan selalu menjadi peran 'user' biasa,
         * bukan admin, demi keamanan sistem secara keseluruhan.
         */
        if (! $user->hasRole('user')) {
            $user->assignRole('user');
        }

        // Login pengguna ke sistem secara resmi
        Auth::login($user, true);

        // Regenerasi sesi untuk menghindari serangan Session Fixation
        request()->session()->regenerate();

        // [UPDATE]: Mencegah user biasa terlempar ke halaman Admin jika sebelumnya ada session url.intended nyangkut
        $intended = session()->pull('url.intended', '/');
        if (str_contains($intended, '/admin')) {
            return redirect('/')
                ->with('success', 'Berhasil masuk menggunakan ' . ucfirst($provider) . '.');
        }

        return redirect()->to($intended)
            ->with('success', 'Berhasil masuk menggunakan ' . ucfirst($provider) . '.');
    }
}
