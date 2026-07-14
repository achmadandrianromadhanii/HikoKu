<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // [UPDATE]: Blokir mutlak jika akun Admin mencoba login lewat pintu User
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            Auth::guard('web')->logout();
            // $request->session()->invalidate(); // [FIX] Dihapus agar tidak bertabrakan dengan sesi Admin
            // $request->session()->regenerateToken(); // [FIX] Dihapus agar CSRF token Admin tidak expired
            
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Akun Admin tidak diizinkan masuk ke halaman User. Silakan login di halaman Admin.',
            ]);
        }

        // [UPDATE]: Mencegah user biasa terlempar ke halaman Admin jika sebelumnya ada session url.intended nyangkut
        $intended = session()->pull('url.intended', '/');
        if (str_contains($intended, '/admin')) {
            return redirect('/');
        }

        return redirect()->to($intended);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // $request->session()->invalidate(); // [FIX] Dihapus agar tidak bertabrakan dengan sesi Admin

        // $request->session()->regenerateToken(); // [FIX] Dihapus agar CSRF token Admin tidak expired

        return redirect('/');
    }
}
