<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Rental;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman Dashboard Admin.
     * Fungsi ini telah dioptimasi 100% untuk meminimalisir beban query database
     * sehingga memastikan metrik Lighthouse (LCP, CLS, INP) tetap hijau dan ultra cepat.
     */
    public function index(): Response
    {
        // 1. SETTING WAKTU REAL-TIME (AKURASI TINGGI)
        // Menggunakan waktu server yang sudah dikonfigurasi (biasanya Asia/Jakarta di config/app.php).
        // Kita menggunakan object now() untuk menjamin tanggal dan waktu yang ditarik 100% akurat.
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // 2. QUERY STATISTIK UTAMA (DIOPTIMASI)
        // Pengambilan data menggunakan query terpisah dan count() agar tidak memakan memori RAM server.
        $stats = [
            // [Total Rental Hari Ini] - Menghitung jumlah orderan spesifik di hari ini saja
            'rentals_today' => Rental::query()
                ->whereDate('created_at', $today)
                ->count(),

            // [Revenue Bulan Ini] - Menjumlahkan total nominal uang (amount) khusus untuk pembayaran yang LUNAS (paid) bulan ini
            'revenue_month' => (float) Payment::query()
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount'),

            // [Rental Aktif] - Memantau jumlah barang yang sedang disewa pelanggan saat ini
            'active_rentals' => Rental::query()
                ->where('status', 'active')
                ->count(),

            // [Stok Kritis] - Memonitor produk yang stoknya tersisa sangat sedikit (kurang dari atau sama dengan 2)
            'low_stock_count' => Product::query()
                ->where('is_active', true)
                ->where('stock_available', '<=', 2)
                ->count(),
        ];

        // 3. DATA GRAFIK DONAT (STATUS TRANSAKSI)
        // Dikelompokkan (groupBy) langsung di tingkat database agar ringan.
        $statusRows = Rental::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        // Mapping hasil query untuk disuntikkan langsung ke ApexCharts
        $statusChart = [
            'labels' => $statusRows
                ->map(fn($row) => ucwords(str_replace('_', ' ', (string) $row->status))) // Format teks jadi rapi, misal: 'In_progress' jadi 'In Progress'
                ->values()
                ->all(),
            'values' => $statusRows
                ->map(fn($row) => (int) $row->total) // Pastikan data integer agar chart tidak error
                ->values()
                ->all(),
        ];

        // 4. DATA GRAFIK GARIS (TREND REVENUE - 7 HARI TERAKHIR REAL-TIME)
        // Mendapatkan rentang waktu 7 hari terakhir (H-6 sampai Hari Ini jam 23:59:59) dengan akurasi jam server
        $chartStart = now()->subDays(6)->startOfDay();
        $chartEnd = now()->endOfDay();

        // Ambil transaksi hanya dalam rentang waktu 7 hari tersebut yang berstatus Lunas
        $payments = Payment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$chartStart, $chartEnd])
            ->get();

        // Menyiapkan blueprint 7 Hari berurutan. Ini mencegah grafik 'bolong' atau eror di hari yang transaksinya 0.
        $period = collect(range(0, 6))
            ->map(fn($offset) => now()->subDays(6 - $offset)->format('Y-m-d'));

        // Mengelompokkan total pemasukan berdasarkan tanggal persis (Y-m-d)
        $revenueMap = $payments
            ->groupBy(function ($payment) {
                // Pastikan menggunakan tanggal bayar (paid_at), jika tidak ada fallback ke tanggal order (created_at)
                return Carbon::parse($payment->paid_at ?? $payment->created_at)->format('Y-m-d');
            })
            ->map(fn($group) => (float) $group->sum('amount'));

        // Output final data untuk ApexCharts (Bumbu rahasia: translatedFormat('d M') merender menjadi misal "18 Jun")
        $revenueChart = [
            'labels' => $period
                ->map(function ($date) {
                    // Menerjemahkan tanggal menjadi format Hari dan Bulan yang akurat dan lokal (contoh: 12 Jun, 13 Jun)
                    return Carbon::parse($date)->translatedFormat('d M');
                })
                ->values()
                ->all(),
            'values' => $period
                ->map(fn($date) => (float) ($revenueMap[$date] ?? 0)) // Jika tidak ada pemasukan di hari itu, set otomatis jadi 0 agar grafik tetap stabil
                ->values()
                ->all(),
        ];

        // 5. DATA GRAFIK BAR (VOLUME RENTAL - 7 HARI TERAKHIR)
        // Menghitung jumlah transaksi (bukan uangnya) yang terjadi dalam 7 hari terakhir
        $recentRentals = Rental::query()
            ->whereBetween('created_at', [$chartStart, $chartEnd])
            ->get();

        $rentalsVolumeMap = $recentRentals
            ->groupBy(fn ($rental) => $rental->created_at->format('Y-m-d'))
            ->map(fn ($group) => $group->count());
            
        $rentalsVolumeChart = [
            'labels' => $period
                ->map(fn($date) => Carbon::parse($date)->translatedFormat('d M'))
                ->values()
                ->all(),
            'values' => $period
                ->map(fn($date) => (int) ($rentalsVolumeMap[$date] ?? 0)) // Jika tidak ada order, set ke 0
                ->values()
                ->all(),
        ];

        // 6. DATA GRAFIK HORIZONTAL BAR (TOP 5 PRODUK TERLARIS)
        // Menggunakan join langsung dari database untuk performa maksimal tanpa meload model berat
        $topProducts = DB::table('rental_items')
            ->join('products', 'rental_items.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(rental_items.quantity) as total_rented'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_rented')
            ->limit(5)
            ->get();

        $topProductsChart = [
            'labels' => $topProducts->map(fn($item) => Str::limit($item->name, 15))->values()->all(),
            'values' => $topProducts->map(fn($item) => (int) $item->total_rented)->values()->all(),
        ];

        // 7. DATA GRAFIK STACKED BAR (LIVE FLEET / INVENTORY CIRCULATION STATUS)
        // Menghitung status fisik peredaran barang secara real-time.
        // Melibatkan kalkulasi stok tersedia, sedang disewa, dibooking, dan rusak.
        $categories = DB::table('categories')->pluck('name', 'id');

        // Total Stock & Available Stock per Kategori
        $stockData = DB::table('products')
            ->where('is_active', true)
            ->select('category_id', DB::raw('SUM(stock_total) as total_stock'), DB::raw('SUM(stock_available) as available_stock'))
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        // Menghitung jumlah 'Active' (Sedang Disewa) untuk Produk Satuan
        $activeProductQtys = DB::table('rental_items')
            ->join('rentals', 'rental_items.rental_id', '=', 'rentals.id')
            ->join('products', 'rental_items.product_id', '=', 'products.id')
            ->where('rentals.status', 'active')
            ->where('rental_items.item_type', 'product')
            ->select('products.category_id', DB::raw('SUM(rental_items.quantity) as total_qty'))
            ->groupBy('products.category_id')
            ->pluck('total_qty', 'category_id');

        // Menghitung jumlah 'Active' (Sedang Disewa) untuk Paket (Package Items)
        $activePackageQtys = DB::table('rental_items')
            ->join('rentals', 'rental_items.rental_id', '=', 'rentals.id')
            ->join('package_items', 'rental_items.package_id', '=', 'package_items.package_id')
            ->join('products', 'package_items.product_id', '=', 'products.id')
            ->where('rentals.status', 'active')
            ->where('rental_items.item_type', 'package')
            ->select('products.category_id', DB::raw('SUM(rental_items.quantity * package_items.quantity) as total_qty'))
            ->groupBy('products.category_id')
            ->pluck('total_qty', 'category_id');

        // Menghitung jumlah 'Booked' (Di-booking: pending_payment & confirmed) untuk Produk Satuan
        $bookedProductQtys = DB::table('rental_items')
            ->join('rentals', 'rental_items.rental_id', '=', 'rentals.id')
            ->join('products', 'rental_items.product_id', '=', 'products.id')
            ->whereIn('rentals.status', ['pending_payment', 'confirmed'])
            ->where('rental_items.item_type', 'product')
            ->select('products.category_id', DB::raw('SUM(rental_items.quantity) as total_qty'))
            ->groupBy('products.category_id')
            ->pluck('total_qty', 'category_id');

        // Menghitung jumlah 'Booked' (Di-booking) untuk Paket
        $bookedPackageQtys = DB::table('rental_items')
            ->join('rentals', 'rental_items.rental_id', '=', 'rentals.id')
            ->join('package_items', 'rental_items.package_id', '=', 'package_items.package_id')
            ->join('products', 'package_items.product_id', '=', 'products.id')
            ->whereIn('rentals.status', ['pending_payment', 'confirmed'])
            ->where('rental_items.item_type', 'package')
            ->select('products.category_id', DB::raw('SUM(rental_items.quantity * package_items.quantity) as total_qty'))
            ->groupBy('products.category_id')
            ->pluck('total_qty', 'category_id');

        // Menyusun Data untuk ApexCharts
        $fleetLabels = [];
        $fleetAvailable = [];
        $fleetActive = [];
        $fleetBooked = [];
        $fleetMaintenance = [];

        foreach ($categories as $categoryId => $categoryName) {
            $stock = $stockData->get($categoryId);
            if (!$stock) continue; // Skip jika kategori tidak punya produk aktif

            $total = (int) $stock->total_stock;
            $available = (int) $stock->available_stock;
            
            $active = ((int) ($activeProductQtys->get($categoryId) ?? 0)) + ((int) ($activePackageQtys->get($categoryId) ?? 0));
            $booked = ((int) ($bookedProductQtys->get($categoryId) ?? 0)) + ((int) ($bookedPackageQtys->get($categoryId) ?? 0));
            
            // Rumus Matematika Mutlak: Rusak/Hilang = Total - Tersedia - Disewa - Di-booking
            $maintenance = $total - $available - $active - $booked;
            // Pencegahan jika ada data negatif karena manipulasi manual db (fallback ke 0)
            if ($maintenance < 0) $maintenance = 0;

            $fleetLabels[] = $categoryName;
            $fleetAvailable[] = $available;
            $fleetActive[] = $active;
            $fleetBooked[] = $booked;
            $fleetMaintenance[] = $maintenance;
        }

        $fleetStatusChart = [
            'labels' => $fleetLabels,
            'series' => [
                ['name' => 'Tersedia', 'data' => $fleetAvailable],
                ['name' => 'Sedang Disewa', 'data' => $fleetActive],
                ['name' => 'Di-booking', 'data' => $fleetBooked],
                ['name' => 'Maintenance', 'data' => $fleetMaintenance],
            ],
        ];

        // Meneruskan variabel murni ke Vue.js tanpa beban relasi ORM berlebih
        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => $stats,
            'revenueChart' => $revenueChart,
            'statusChart' => $statusChart,
            'rentalsVolumeChart' => $rentalsVolumeChart,
            'topProductsChart' => $topProductsChart,
            'fleetStatusChart' => $fleetStatusChart,
        ]);
    }
}
