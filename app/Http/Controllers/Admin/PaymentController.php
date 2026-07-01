<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\RentalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected RentalService $rentalService
    ) {}

    /**
     * Menampilkan daftar pembayaran dengan fitur pencarian dan filter.
     * Menggunakan pagination dari Laravel dan di-mapping untuk Inertia.
     */
    public function index(Request $request): Response
    {
        // Parameter filter dari URL
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'method' => $request->input('method'),
        ];

        // Query dasar Payment beserta relasinya
        $query = Payment::query()->with(['rental.user']);

        // Filter berdasarkan pencarian (kode pembayaran, kode rental, nama, email)
        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('payment_code', 'like', "%{$search}%")
                    ->orWhereHas('rental', function ($rentalQuery) use ($search) {
                        $rentalQuery->where('rental_code', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($userQuery) use ($search) {
                                $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Filter berdasarkan status pembayaran
        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        // Filter berdasarkan metode pembayaran
        if ($filters['method']) {
            $query->where('method', $filters['method']);
        }

        // Paginasi query
        $paginator = $query->latest('id')->paginate(10)->withQueryString();

        // Mapping paginator menjadi struktur object untuk frontend Vue
        $paymentsPayload = [
            'data' => collect($paginator->items())->map(function ($payment) {
                $rental = $payment->rental;
                $user = $rental?->user;

                $method = $payment->method ?? '-';
                $status = $payment->status ?? 'pending';

                return [
                    'id' => $payment->id,
                    'payment_code' => $payment->payment_code ?? ('PAY-' . $payment->id),
                    'rental_id' => $rental?->id,
                    'rental_code' => $rental?->rental_code ?? '-',
                    'user_name' => $user?->name ?? '-',
                    'user_email' => $user?->email ?? '-',
                    'method' => $method,
                    'amount_label' => 'Rp ' . number_format((float) $payment->amount, 0, ',', '.'),
                    'status' => $status,
                    'created_at_label' => optional($payment->created_at)->format('d M Y H:i'),
                    'is_manual_confirmable' => in_array($method, ['cash', 'transfer_manual'], true) && $status === 'pending',
                ];
            })->all(),
            'links' => $paginator->linkCollection()->toArray(),
        ];

        // Hitung statistik pembayaran (tanpa mengambil data ulang secara masif)
        $stats = [
            'pending' => Payment::query()->where('status', 'pending')->count(),
            'paid' => Payment::query()->where('status', 'paid')->count(),
            'failed' => Payment::query()->whereIn('status', ['failed', 'expired', 'cancelled'])->count(),
            'refunded' => Payment::query()->where('status', 'refunded')->count(),
        ];

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $paymentsPayload,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * Konfirmasi pembayaran secara manual (misal: pembayaran kasir / transfer).
     */
    public function confirm(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Hanya payment pending yang bisa dikonfirmasi.');
        }

        DB::transaction(function () use ($payment, $request) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'confirmed_by' => $request->user()->id,
            ]);

            // Panggil service rental untuk mengeksekusi logika pasca-pembayaran (jika ada)
            if ($payment->rental && $payment->rental->status === 'pending_payment') {
                $this->rentalService->confirmRental($payment->rental, $request->user()->id);
            }
        });

        return back()->with('success', 'Pembayaran dikonfirmasi secara manual.');
    }
}
