<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\RentalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Mail\RentalConfirmedMail;
use Inertia\Inertia;
use Inertia\Response;

class RentalController extends Controller
{
    public function __construct(
        protected RentalService $rentalService
    ) {}

    public function index(): Response
    {
        $filters = [
            'search' => request('search'),
            'status' => request('status'),
        ];

        $query = Rental::query();

        $rentalInstance = new Rental();

        $with = [];

        if (method_exists($rentalInstance, 'user')) {
            $with[] = 'user';
        }

        if (method_exists($rentalInstance, 'payment')) {
            $with[] = 'payment';
        }

        if (method_exists($rentalInstance, 'payments')) {
            $with[] = 'payments';
        }

        if (! empty($with)) {
            $query->with($with);
        }

        if ($filters['search']) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search, $rentalInstance) {
                $q->where('rental_code', 'like', "%{$search}%");

                if (method_exists($rentalInstance, 'user')) {
                    $q->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            });
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        $rentals = $query->latest('id')
            ->paginate(10)
            ->withQueryString();

        $rentalData = collect($rentals->items())->map(function ($rental) {
            $payment = null;

            if (method_exists($rental, 'payment') && $rental->relationLoaded('payment')) {
                $payment = $rental->payment;
            } elseif (method_exists($rental, 'payments') && $rental->relationLoaded('payments')) {
                $payment = $rental->payments->first();
            }

            $startDate = $rental->rental_start ?? $rental->start_date ?? null;
            $endDate = $rental->rental_end ?? $rental->end_date ?? null;

            return [
                'id' => $rental->id,
                'rental_code' => $rental->rental_code ?? '-',
                'user_name' => $rental->user?->name ?? '-',
                'user_email' => $rental->user?->email ?? '-',
                'period_label' => trim(
                    (optional($startDate)?->format('d M Y') ?? '-') . ' - ' .
                        (optional($endDate)?->format('d M Y') ?? '-')
                ),
                'status' => $rental->status ?? 'pending_payment',
                'grand_total_label' => 'Rp ' . number_format((float) ($rental->grand_total ?? 0), 0, ',', '.'),
                'payment_status' => $payment?->status,
            ];
        })->values()->all();

        return Inertia::render('Admin/Rentals/Index', [
            'rentals' => [
                'data' => $rentalData,
                'links' => $rentals->linkCollection()->values()->all(),
            ],
            'filters' => $filters,
            'stats' => [
                'pending_payment' => Rental::query()->where('status', 'pending_payment')->count(),
                'confirmed' => Rental::query()->where('status', 'confirmed')->count(),
                'active' => Rental::query()->where('status', 'active')->count(),
                'overdue' => Rental::query()->where('status', 'overdue')->count(),
            ],
        ]);
    }

    public function show(Rental $rental): Response
    {
        $relations = [];

        if (method_exists($rental, 'user')) {
            $relations[] = 'user';
        }

        if (method_exists($rental, 'items')) {
            $relations[] = 'items';
        }

        if (method_exists($rental, 'guarantee')) {
            $relations[] = 'guarantee';
        }

        if (method_exists($rental, 'payment')) {
            $relations[] = 'payment';
        }

        if (method_exists($rental, 'payments')) {
            $relations[] = 'payments';
        }

        if (! empty($relations)) {
            $rental->loadMissing($relations);
        }

        $items = collect();

        if (method_exists($rental, 'items') && $rental->relationLoaded('items')) {
            $items = $rental->items->map(function ($item) {
                if (method_exists($item, 'product')) {
                    $item->loadMissing('product');
                }

                if (method_exists($item, 'package')) {
                    $item->loadMissing('package');
                }

                if (method_exists($item, 'productPackage')) {
                    $item->loadMissing('productPackage');
                }

                $productName = $item->product?->name
                    ?? $item->package?->name
                    ?? $item->productPackage?->name
                    ?? 'Item';

                $itemType = $item->item_type
                    ?? (! empty($item->product_id) ? 'product' : 'package');

                return [
                    'id' => $item->id,
                    'item_type' => $itemType,
                    'product_name' => $productName,
                    'quantity' => (int) ($item->quantity ?? 1),
                    'subtotal' => (float) ($item->subtotal ?? 0),
                ];
            })->values();
        }

        $payment = null;

        if (method_exists($rental, 'payment') && $rental->relationLoaded('payment')) {
            $payment = $rental->payment;
        } elseif (method_exists($rental, 'payments') && $rental->relationLoaded('payments')) {
            $payment = $rental->payments->first();
        }

        $startDate = $rental->rental_start ?? $rental->start_date ?? null;
        $endDate = $rental->rental_end ?? $rental->end_date ?? null;

        return Inertia::render('Admin/Rentals/Show', [
            'rental' => [
                'id' => $rental->id,
                'rental_code' => $rental->rental_code ?? '-',
                'status' => $rental->status ?? 'pending_payment',
                'created_at' => optional($rental->created_at)?->toIso8601String(),
                'rental_start' => optional($startDate)?->toIso8601String(),
                'rental_end' => optional($endDate)?->toIso8601String(),
                'subtotal' => (float) ($rental->subtotal ?? 0),
                'discount_amount' => (float) ($rental->discount_amount ?? 0),
                'deposit_total' => (float) ($rental->deposit_total ?? 0),
                'late_fee' => (float) ($rental->late_fee ?? 0),
                'grand_total' => (float) ($rental->grand_total ?? 0),

                'user' => $rental->user ? [
                    'id' => $rental->user->id,
                    'name' => $rental->user->name,
                    'email' => $rental->user->email,
                    'phone' => $rental->user->phone,
                ] : null,

                'items' => $items,

                'guarantee' => $rental->guarantee ? [
                    'id' => $rental->guarantee->id,
                    'type' => $rental->guarantee->type,
                    'ktp_name' => $rental->guarantee->ktp_name,
                    'ktp_number' => $rental->guarantee->ktp_number,
                    'item_name' => $rental->guarantee->item_name ?? null,
                    'item_description' => $rental->guarantee->item_description ?? null,
                    'is_returned' => (bool) ($rental->guarantee->is_returned ?? false),
                ] : null,

                'payment' => $payment ? [
                    'id' => $payment->id,
                    'payment_code' => $payment->payment_code,
                    'status' => $payment->status,
                    'payment_method' => $payment->method,
                    'amount' => (float) ($payment->amount ?? 0),
                    'paid_at' => optional($payment->paid_at)?->toIso8601String(),
                ] : null,
            ],
        ]);
    }

    public function saveGuarantee(Request $request, Rental $rental): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:ktp,barang'],
            'ktp_name' => ['nullable', 'string', 'max:255'],
            'ktp_number' => ['nullable', 'string', 'max:100'],
            'item_name' => ['nullable', 'string', 'max:255'],
            'item_description' => ['nullable', 'string'],
        ]);

        if (! in_array($rental->status, ['confirmed', 'active', 'overdue', 'returned'], true)) {
            throw ValidationException::withMessages([
                'guarantee' => 'Jaminan hanya bisa diinput setelah rental confirmed.',
            ]);
        }

        if ($data['type'] === 'ktp') {
            if (blank($data['ktp_name']) || blank($data['ktp_number'])) {
                throw ValidationException::withMessages([
                    'ktp_name' => 'Nama KTP wajib diisi.',
                    'ktp_number' => 'Nomor KTP wajib diisi.',
                ]);
            }
        }

        if ($data['type'] === 'barang' && blank($data['item_name'])) {
            throw ValidationException::withMessages([
                'item_name' => 'Nama barang jaminan wajib diisi.',
            ]);
        }

        DB::transaction(function () use ($rental, $data) {
            $rental->loadMissing('guarantee');

            $payload = $this->buildGuaranteePayload($data);

            if ($rental->guarantee) {
                $rental->guarantee->update($payload);
            } else {
                $rental->guarantee()->create($payload);
            }
        });

        return back()->with('success', 'Jaminan berhasil disimpan.');
    }

    public function confirm(Rental $rental): RedirectResponse
    {
        $rental->load('payment');
        $payment = $rental->payment;

        // [UPDATE]: Jika admin klik konfirmasi manual, otomatis sinkronkan status payment menjadi 'paid' 
        // agar tidak terjadi data ganda (Rental Confirmed tapi Payment Pending).
        if ($payment && $payment->status !== 'paid') {
            app(\App\Services\PaymentService::class)->confirmPayment(
                $payment, 
                'paid', 
                null, 
                'Manual confirmation by Admin'
            );
            $rental->refresh();
        } else {
            $this->rentalService->confirmRental($rental, Auth::id());
        }

        // [UPDATE]: Pengiriman email tidak lagi dilakukan di sini karena sudah dipindahkan 
        // ke RentalService@confirmRental, sehingga Midtrans maupun Admin bisa memicu pengiriman email.

        return back()->with('success', 'Rental dan Pembayaran berhasil dikonfirmasi secara sinkron.');
    }

    public function activate(Rental $rental): RedirectResponse
    {
        $this->rentalService->activateRental($rental);

        return back()->with('success', 'Rental berhasil diaktifkan.');
    }

    public function processReturn(Request $request, Rental $rental): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.condition' => 'required|in:good,damaged,missing',
            'items.*.missing_qty' => 'nullable|integer|min:0',
            'items.*.damaged_qty' => 'nullable|integer|min:0',
            'items.*.notes' => 'nullable|string',
            'items.*.penalty' => 'nullable|numeric|min:0',
        ]);

        $itemsConditions = $validated['items'] ?? [];

        $this->rentalService->processReturn($rental, $itemsConditions, Auth::id());

        return back()->with('success', 'Barang berhasil dikembalikan beserta rekap denda (jika ada).');
    }

    public function dispute(Rental $rental): RedirectResponse
    {
        $rental->update(['status' => 'disputed']);
        return back()->with('success', 'Rental ditandai Bermasalah. KTP harap ditahan sampai ada ganti rugi.');
    }

    protected function buildGuaranteePayload(array $data): array
    {
        $table = 'guarantees';
        $payload = [];

        if (Schema::hasColumn($table, 'type')) {
            $payload['type'] = $data['type'];
        }

        if (Schema::hasColumn($table, 'ktp_name')) {
            $payload['ktp_name'] = $data['type'] === 'ktp' ? ($data['ktp_name'] ?: null) : null;
        }

        if (Schema::hasColumn($table, 'ktp_number')) {
            $payload['ktp_number'] = $data['type'] === 'ktp' ? ($data['ktp_number'] ?: null) : null;
        }

        if (Schema::hasColumn($table, 'item_name')) {
            $payload['item_name'] = $data['type'] === 'barang' ? ($data['item_name'] ?: null) : null;
        }

        if (Schema::hasColumn($table, 'item_description')) {
            $payload['item_description'] = $data['type'] === 'barang' ? ($data['item_description'] ?: null) : null;
        }

        if (Schema::hasColumn($table, 'is_returned')) {
            $payload['is_returned'] = false;
        }

        if (Schema::hasColumn($table, 'returned_at')) {
            $payload['returned_at'] = null;
        }

        if (Schema::hasColumn($table, 'returned_by')) {
            $payload['returned_by'] = null;
        }

        return $payload;
    }
}
