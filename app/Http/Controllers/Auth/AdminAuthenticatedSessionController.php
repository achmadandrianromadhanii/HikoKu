<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthenticatedSessionController extends Controller
{
    /**
     * Tampilkan halaman login admin.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/Login');
    }

    /**
     * Proses login admin.
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            // Menghapus validasi 'email' agar input 'admin' bisa masuk (bukan hanya format email@domain.com)
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if (! Auth::guard('admin')->attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            $credentials['remember'] ?? false
        )) {
            throw ValidationException::withMessages([
                'email' => 'Username atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard('admin')->user();

        if (! $user || ! $user->hasRole('admin')) {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages([
                'email' => 'Akun ini bukan admin.',
            ]);
        }

        if (! $user->is_active) {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages([
                'email' => 'Akun admin sedang nonaktif.',
            ]);
        }

        return redirect()->route('admin.dashboard')
            ->with('success', 'Login admin berhasil.');
    }

    /**
     * Logout admin.
     */
    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();

        // $request->session()->invalidate(); // [FIX] Dihapus agar tidak logout user saat admin logout
        // $request->session()->regenerateToken(); // [FIX] Dihapus agar token CSRF tab user tidak kedaluwarsa

        return redirect()->route('admin.login');
    }
}
