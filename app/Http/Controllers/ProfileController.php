<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            // [KOMENTAR PENJELASAN]: Mengambil SELURUH data rental (bukan paginate) agar data tiket realistis dan tampil 100% lengkap di desktop maupun mobile
            'rentals' => $request->user()->rentals()
                ->with('items.product')
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        if ($request->user()->email === 'admin') {
            return Redirect::route('profile.edit')->with('error', 'Akun admin utama tidak dapat diubah kredensialnya.');
        }

        // [KOMENTAR PENJELASAN]: Mengambil data validasi (termasuk name, email, phone)
        $user = $request->user();
        $validated = $request->validated();
        
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // [KOMENTAR PENJELASAN]: Menangani proses upload Foto Profil (Avatar)
        if ($request->hasFile('avatar')) {
            // [KOMENTAR PENJELASAN]: Hapus avatar lama dari storage jika bukan link eksternal (sosmed)
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete($user->avatar);
            }
            // [KOMENTAR PENJELASAN]: Simpan avatar baru ke folder 'avatars' secara permanen
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()->email === 'admin') {
            return Redirect::route('profile.edit')->with('error', 'Akun admin utama tidak dapat dihapus.');
        }

        $user = $request->user();

        // [KOMENTAR PENJELASAN]: Lewati validasi password jika user login menggunakan sosial media (karena mereka diotentikasi dari provider).
        $isSocialLogin = (bool) ($user->google_id || $user->github_id || $user->discord_id);
        
        if (!$isSocialLogin) {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Toggle status preferensi notifikasi email.
     */
    public function toggleNotification(Request $request): RedirectResponse
    {
        $request->user()->update([
            'notification_email' => !$request->user()->notification_email,
        ]);
        return Redirect::back();
    }
}
