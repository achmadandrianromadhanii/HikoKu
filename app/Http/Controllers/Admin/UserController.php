<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Menampilkan halaman daftar user untuk Admin.
     * Termasuk fitur pencarian, filter role, dan filter status aktif.
     */
    public function index(Request $request): Response
    {
        // 1. Ambil parameter filter dari URL
        $filters = [
            'search' => $request->input('search'),
            'role' => $request->input('role'),
            'active_only' => $request->input('active_only'),
        ];

        // 2. Query dasar User
        $query = User::query();

        // Relasi ke tabel roles menggunakan spatie/laravel-permission (jika ada)
        if (method_exists(new User(), 'roles')) {
            $query->with('roles:id,name');
        }

        // 3. Filter Search (mencari berdasar nama, email, telepon)
        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 4. Filter berdasarkan Role
        if ($filters['role'] && method_exists(new User(), 'roles')) {
            $query->whereHas('roles', function ($roleQuery) use ($filters) {
                $roleQuery->where('name', $filters['role']);
            });
        }

        // 5. Filter berdasarkan Status Aktif/Nonaktif
        if ($filters['active_only'] !== null && $filters['active_only'] !== '') {
            $query->where('is_active', (bool) $filters['active_only']);
        }

        // 6. Jalankan Paginasi Laravel
        $paginator = $query->latest('id')->paginate(10)->withQueryString();

        // 7. Format data ke array agar aman dibaca frontend Inertia Vue
        $usersPayload = [
            'data' => collect($paginator->items())->map(function ($user) {
                $role = method_exists($user, 'roles')
                    ? ($user->roles->first()?->name ?? 'user')
                    : 'user';

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $role,
                    'is_active' => (bool) $user->is_active,
                    'email_verified_at' => $user->email_verified_at,
                    'joined_at_label' => optional($user->created_at)->format('d M Y'),
                ];
            })->all(),
            'links' => $paginator->linkCollection()->toArray(),
        ];

        // 8. Hitung statistik dasar untuk card di dashboard user
        $stats = [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
            'admins' => method_exists(new User(), 'roles')
                ? User::query()->whereHas('roles', fn($q) => $q->where('name', 'admin'))->count()
                : 0,
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $usersPayload,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * Mengaktifkan atau menonaktifkan status user.
     * Admin tidak boleh menonaktifkan akunnya sendiri.
     */
    public function toggleActive(User $user, Request $request)
    {
        // Cegah Admin menonaktifkan dirinya sendiri
        if ((int) $user->id === (int) $request->user()->id) {
            return back()->with('error', 'Admin tidak boleh menonaktifkan akun sendiri.');
        }

        $user->update([
            'is_active' => ! (bool) $user->is_active,
        ]);

        return back()->with('success', 'Status user berhasil diperbarui.');
    }
}
