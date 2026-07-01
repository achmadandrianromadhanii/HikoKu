<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScannerController extends Controller
{
    public function __construct(
        protected RentalService $rentalService
    ) {}

    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'rental_code' => ['required', 'string'],
        ]);

        $rental = Rental::with(['user', 'items.product', 'items.package.items.product'])
            ->where('rental_code', $request->rental_code)
            ->first();

        if (!$rental) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan! Pastikan kode scan valid.',
                'action_status' => 'error'
            ], 404);
        }

        // Logic 1: Confirmed -> Activate (Ambil Barang)
        if ($rental->status === 'confirmed') {
            $this->rentalService->activateRental($rental);
            return response()->json([
                'success' => true,
                'message' => "Sukses! Pesanan {$rental->rental_code} (atas nama {$rental->user->name}) telah aktif. Silakan serahkan barang.",
                'action_status' => 'activated',
                'rental' => $rental
            ]);
        }

        // Logic 2: Active / Overdue -> Return (Kembalikan Barang)
        if (in_array($rental->status, ['active', 'overdue'])) {
            $now = Carbon::now();
            $end = Carbon::parse($rental->rental_end)->endOfDay();

            // Cek Keterlambatan
            if ($now->greaterThan($end)) {
                // Pembulatan ke atas untuk jumlah hari terlambat
                $daysLate = (int) ceil($now->diffInHours($end) / 24);
                $daysLate = max(1, $daysLate); // Minimal 1 hari jika sudah lewat endOfDay
                
                // Tarif denda: misal Rp 50.000 / hari keterlambatan
                $lateFee = $daysLate * 50000;

                return response()->json([
                    'success' => false,
                    'message' => "Terlambat {$daysLate} hari! Tagih Denda Tunai: Rp " . number_format($lateFee, 0, ',', '.') . " sebelum memproses Return.",
                    'action_status' => 'late',
                    'late_fee' => $lateFee,
                    'rental' => $rental
                ]);
            }

            // Tepat Waktu -> Return
            $this->rentalService->processReturn($rental, $request->user()->id);
            return response()->json([
                'success' => true,
                'message' => "Pengembalian berhasil! Stok pesanan {$rental->rental_code} telah dikembalikan ke katalog.",
                'action_status' => 'returned',
                'rental' => $rental
            ]);
        }

        // Logic 3: Status lain yang tidak valid discan (Pending, Cancelled, Returned, Disputed)
        $statusLabels = [
            'pending_payment' => 'Menunggu Pembayaran',
            'returned' => 'Sudah Dikembalikan',
            'cancelled' => 'Dibatalkan / Hangus',
            'overdue' => 'Terlambat (Overdue)',
            'disputed' => 'Bermasalah (Disputed)',
        ];

        $currentStatus = $statusLabels[$rental->status] ?? $rental->status;

        return response()->json([
            'success' => false,
            'message' => "TIKET HANGUS / TIDAK VALID! Status saat ini: {$currentStatus}.",
            'action_status' => 'error',
            'rental' => $rental
        ], 400);
    }
}
