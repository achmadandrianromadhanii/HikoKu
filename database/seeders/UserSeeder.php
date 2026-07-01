<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Menciptakan satu akun admin utama dengan kredensial sederhana
        $admin = User::updateOrCreate(
            ['email' => 'admin'],
            [
                'name' => 'Admin Utama',
                'phone' => '081111111111',
                'password' => Hash::make('admin'), // Sandi diubah menjadi 'admin'
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        
        // Memastikan admin mendapatkan peran 'admin'
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Hapus data pengguna dummy lainnya untuk lingkungan yang lebih bersih
        User::where('email', '!=', 'admin')->delete();
    }
}
