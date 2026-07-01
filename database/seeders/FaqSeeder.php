<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['question' => 'Bagaimana cara menyewa?', 'answer' => 'Pilih produk, tentukan tanggal, lalu checkout.'],
            ['question' => 'Apakah harus login dulu?', 'answer' => 'Ya, login dibutuhkan untuk checkout dan riwayat sewa.'],
            ['question' => 'Apakah bisa bayar online?', 'answer' => 'Bisa, pembayaran online menggunakan Midtrans.'],
            ['question' => 'Apakah stok bisa dicek sebelum login?', 'answer' => 'Bisa, stok tersedia bisa dilihat di halaman publik.'],
            ['question' => 'Bagaimana kalau telat mengembalikan?', 'answer' => 'Akan dikenakan denda keterlambatan sesuai aturan toko.'],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
