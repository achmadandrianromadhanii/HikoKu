<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Services\RentalService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class RentalController extends Controller
{
    public function __construct(
        protected RentalService $rentalService,
        protected PaymentService $paymentService
    ) {}

    public function store(Request $request): RedirectResponse | JsonResponse
    {
        $data = $request->validate([
            'rental_start' => ['required', 'date'],
            'rental_end' => ['required', 'date', 'after_or_equal:rental_start'],
            'notes' => ['nullable', 'string'],
        ]);

        $rental = $this->rentalService->createFromCart(
            userId: $request->user()->id,
            rentalStart: $data['rental_start'],
            rentalEnd: $data['rental_end'],
            notes: $data['notes'] ?? null
        );

        $payment = $rental->payment;
        if (! $payment || ! $payment->snap_token) {
            $payment = $this->paymentService->createPayment($rental);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'rental_code' => $rental->rental_code,
                'snap_token' => $payment->snap_token,
            ]);
        }

        return redirect()
            ->route('my-rentals.show', $rental->rental_code)
            ->with('success', 'Checkout berhasil dibuat.');
    }

    public function index(Request $request): Response
    {
        $rentals = Rental::query()
            ->with([
                'items',
                'payment',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MyRentals/Index', [
            'rentals' => $rentals,
        ]);
    }

    public function show(Request $request, string $code): Response
    {
        $rental = Rental::query()
            ->with([
                'items.product.images',
                'items.package.items.product.images',
                'payment',
                'invoice',
            ])
            ->where('user_id', $request->user()->id)
            ->where('rental_code', $code)
            ->firstOrFail();

        return Inertia::render('MyRentals/Show', [
            'rental' => $rental,
            'reorderSuggestions' => [], // Fitur reorder dihapus sesuai rencana
        ]);
    }

    public function cancel(Request $request, string $code): RedirectResponse
    {
        $rental = Rental::query()
            ->where('user_id', $request->user()->id)
            ->where('rental_code', $code)
            ->firstOrFail();

        if ($rental->status !== 'pending_payment') {
            return back()->with('error', 'Rental tidak bisa dibatalkan.');
        }

        // Gunakan service agar stok yang terpotong saat checkout dikembalikan
        $this->rentalService->cancelRental($rental);

        return back()->with('success', 'Rental dibatalkan dan stok telah dikembalikan.');
    }
}
