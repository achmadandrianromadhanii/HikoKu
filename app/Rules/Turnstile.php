<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    /**
     * Run the validation rule.
     * 
     * Penjelasan:
     * Fungsi ini bertugas memverifikasi token rahasia (response) yang dikirim oleh
     * widget Turnstile di sisi frontend. Kita menembak API resmi Cloudflare dengan 
     * menyertakan Secret Key untuk memastikan bahwa token ini asli dan valid.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Jika token kosong (misal bot melewati validasi JS), langsung gagalkan
        if (empty($value)) {
            $fail('Verifikasi keamanan gagal. Silakan coba lagi.');
            return;
        }

        // [UPDATE ANTI-ERROR]: Mencegah error login di environment pengembangan lokal.
        // Karena script Turnstile frontend dipaksa menggunakan Dummy Key di HTTP localhost (untuk mencegah error console),
        // maka token yang dikirim adalah dummy. Kita HARUS mem-bypass tembakan API asli Cloudflare di sini agar login bisa lanjut.
        $isLocalhost = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']);
        if ($isLocalhost && !request()->secure()) {
            return; // Sukses secara otomatis di HTTP Localhost
        }

        // Tembak API resmi verifikasi Cloudflare Turnstile
        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret'),
            'response' => $value,
            'remoteip' => request()->ip(), // Mengirim IP address user sebagai lapisan keamanan tambahan
        ]);

        // Cek response dari Cloudflare, jika sukses bernilai false, gagalkan form
        if (! $response->json('success')) {
            $fail('Verifikasi keamanan gagal atau kadaluarsa. Silakan muat ulang halaman.');
        }
    }
}
