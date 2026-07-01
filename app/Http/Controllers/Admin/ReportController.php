<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Menampilkan halaman Laporan Admin.
     * Mengambil data pendapatan bulanan dan jumlah sewa berdasarkan tahun berjalan.
     */
    public function index(Request $request): Response
    {
        // Mendapatkan tahun saat ini, atau dari request jika ada filter tahun
        $year = $request->input('year', Carbon::now()->year);

        // [UPDATE]: Menyesuaikan query agar 100% kompatibel dengan PostgreSQL (dan MySQL).
        // Fungsi MONTH() bawaan MySQL diganti menjadi standar SQL EXTRACT(MONTH FROM).
        // Menggunakan groupByRaw karena PostgreSQL Strict Mode tidak mengizinkan GROUP BY menggunakan alias.
        $monthlyPayments = Payment::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(amount) as total_income, COUNT(id) as total_rentals')
            ->where('status', 'paid')
            ->whereYear('created_at', $year)
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->orderBy('month')
            ->get()
            ->keyBy(function($item) {
                // Memastikan tipe data key (bulan) adalah integer, karena PDO postgres terkadang me-return float/string.
                return (int) $item->month;
            });

        // 2. Format data bulanan agar terisi dari Januari (1) sampai Desember (12)
        // Jika di bulan tersebut tidak ada transaksi, maka diset 0.
        $monthlyReport = [];
        $totalIncomeYear = 0;
        $totalRentalsYear = 0;

        for ($i = 1; $i <= 12; $i++) {
            $data = $monthlyPayments->get($i);
            $income = $data ? (float) $data->total_income : 0;
            $rentals = $data ? (int) $data->total_rentals : 0;

            $monthlyReport[] = [
                'month' => $i,
                'month_name' => Carbon::create()->month($i)->translatedFormat('F'),
                'income' => $income,
                'rentals' => $rentals,
            ];

            $totalIncomeYear += $income;
            $totalRentalsYear += $rentals;
        }

        // 3. Mengambil ringkasan bulan ini (Current Month)
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        $currentMonthIncome = Payment::where('status', 'paid')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('amount');
            
        $currentMonthRentals = Payment::where('status', 'paid')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        // 4. Mengembalikan data ke tampilan Frontend (Vue Inertia)
        return Inertia::render('Admin/Reports/Index', [
            'year' => (int) $year,
            'monthlyReport' => $monthlyReport,
            'stats' => [
                'current_month_income' => (float) $currentMonthIncome,
                'current_month_rentals' => (int) $currentMonthRentals,
                'total_income_year' => $totalIncomeYear,
                'total_rentals_year' => $totalRentalsYear,
            ]
        ]);
    }
}
