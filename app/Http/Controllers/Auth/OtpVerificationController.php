<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class OtpVerificationController extends Controller
{
    /**
     * Tampilkan halaman verifikasi OTP.
     */
    public function index(Request $request)
    {
        // Jika sudah terverifikasi, langsung kembalikan ke profil
        if ($request->user()->email_verified_at !== null) {
            return redirect()->route('profile.edit');
        }

        return Inertia::render('Auth/VerifyEmailOtp', [
            'email' => $request->user()->email,
        ]);
    }

    /**
     * Kirim kode OTP ke email.
     */
    public function sendOtp(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return back()->with('error', 'Email Anda sudah terverifikasi.');
        }

        $blockKey = 'otp_block_' . $user->id;
        $attemptsKey = 'otp_attempts_' . $user->id;

        // Cek apakah pengguna sedang diblokir 2 hari
        if (Cache::has($blockKey)) {
            return back()->withErrors(['otp' => 'Anda telah melebihi batas pengiriman. Fitur OTP dinonaktifkan sementara selama 2 hari.']);
        }

        // Tambah jumlah percobaan
        $attempts = Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addHours(1)); // Reset attempt dalam 1 jam jika tidak tembus limit

        // Jika melewati batas 5 kali, blokir selama 2 hari
        if ($attempts > 5) {
            Cache::put($blockKey, true, now()->addDays(2));
            return back()->withErrors(['otp' => 'Terlalu banyak percobaan pengiriman. Anda diblokir selama 2 hari untuk keamanan.']);
        }

        // Generate 6 digit OTP acak
        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Simpan ke Cache selama 5 menit
        $otpKey = 'otp_code_' . $user->id;
        Cache::put($otpKey, $otpCode, now()->addMinutes(5));

        // Kirim email
        // [OPTIMASI VERCEL/LATENCY]: Gunakan afterResponse() agar email dikirim 
        // setelah HTTP Response sukses ke klien (Loading cepat tanpa lag)
        try {
            dispatch(function () use ($user, $otpCode) {
                Mail::to($user->email)->send(new OtpVerificationMail($otpCode));
            })->afterResponse();
        } catch (\Exception $e) {
            return back()->withErrors(['otp' => 'Gagal mengirim email. Pastikan koneksi dan pengaturan SMTP Anda benar.']);
        }

        return back()->with('success', 'Kode OTP 6 digit telah dikirim ke email Anda. Silakan cek Inbox atau Spam.');
    }

    /**
     * Verifikasi kode OTP.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $otpKey = 'otp_code_' . $user->id;
        $cachedOtp = Cache::get($otpKey);

        if (!$cachedOtp) {
            return back()->withErrors(['otp' => 'Kode OTP telah kadaluarsa. Silakan kirim ulang kode baru.']);
        }

        if ($request->code !== $cachedOtp) {
            return back()->withErrors(['otp' => 'Kode OTP yang Anda masukkan salah.']);
        }

        // Berhasil!
        $user->email_verified_at = now();
        $user->save();

        // Bersihkan cache terkait OTP
        Cache::forget($otpKey);
        Cache::forget('otp_attempts_' . $user->id);

        return redirect()->route('profile.edit')->with('success', 'Email Anda berhasil diverifikasi!');
    }
}
