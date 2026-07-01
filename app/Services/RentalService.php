<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Rental;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Mail\RentalConfirmedMail;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class RentalService
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Membuat rental dari keranjang, langsung memotong stok untuk mencegah overselling.
     */
    public function createFromCart(int $userId, string $rentalStart, string $rentalEnd, ?string $notes = null): Rental
    {
        $this->cartService->ensureNoExpiredItems($userId);

        return DB::transaction(function () use ($userId, $rentalStart, $rentalEnd, $notes) {
            $items = CartItem::query()
                ->with(['product', 'package.items.product'])
                ->where('user_id', $userId)
                ->active()
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Keranjang kosong.',
                ]);
            }

            $start = Carbon::parse($rentalStart);
            $end = Carbon::parse($rentalEnd);
            $days = max(1, $start->diffInDays($end) + 1);

            $subtotal = 0;
            $normalizedItems = [];

            foreach ($items as $item) {
                if ($item->item_type === 'product') {
                    $product = Product::query()
                        ->whereKey($item->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ((int) $product->stock_available < (int) $item->quantity) {
                        throw ValidationException::withMessages([
                            'cart' => "Stok {$product->name} tidak mencukupi.",
                        ]);
                    }

                    // LANGSUNG POTONG STOK SAAT CHECKOUT (Fix Overselling)
                    $product->decrement('stock_available', (int) $item->quantity);

                    $itemSubtotal = (int) $item->quantity * (float) $product->price_per_day * $days;

                    $subtotal += $itemSubtotal;

                    $normalizedItems[] = [
                        'item_type' => 'product',
                        'product_id' => $product->id,
                        'package_id' => null,
                        'product_name' => $product->name,
                        'quantity' => (int) $item->quantity,
                        'price_per_day' => (float) $product->price_per_day,
                        'subtotal' => $itemSubtotal,
                    ];
                } else {
                    $package = $item->package()->with('items.product')->firstOrFail();

                    foreach ($package->items as $packageItem) {
                        $product = Product::query()
                            ->whereKey($packageItem->product_id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        $neededQty = (int) $packageItem->quantity * (int) $item->quantity;

                        if ((int) $product->stock_available < $neededQty) {
                            throw ValidationException::withMessages([
                                'cart' => "Stok {$product->name} untuk paket {$package->name} tidak mencukupi.",
                            ]);
                        }

                        // LANGSUNG POTONG STOK
                        $product->decrement('stock_available', $neededQty);
                    }

                    $itemSubtotal = (int) $item->quantity * (float) $package->price_per_day * $days;

                    $subtotal += $itemSubtotal;

                    $normalizedItems[] = [
                        'item_type' => 'package',
                        'product_id' => null,
                        'package_id' => $package->id,
                        'product_name' => $package->name,
                        'quantity' => (int) $item->quantity,
                        'price_per_day' => (float) $package->price_per_day,
                        'subtotal' => $itemSubtotal,
                    ];
                }
            }

            // Subtotal = item costs
            // Grand Total is purely item costs minus discount (if any)
            $grandTotal = $subtotal; // Diskon akan ditambahkan fiturnya nanti jika ada
            $discountAmount = 0; // Default

            $rental = Rental::create([
                'rental_code' => 'GH-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'user_id' => $userId,
                'status' => 'pending_payment',
                'rental_start' => $start,
                'rental_end' => $end,
                'total_days' => $days,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_rental' => $subtotal,
                'grand_total' => $grandTotal,
                'notes' => $notes,
            ]);

            foreach ($normalizedItems as $normalizedItem) {
                $rental->items()->create($normalizedItem);
            }

            if (method_exists($rental, 'invoice')) {
                $rental->invoice()->create([
                    'invoice_number' => 'INV-' . now()->format('Y') . '-' . str_pad((string) $rental->id, 4, '0', STR_PAD_LEFT),
                    'type' => 'invoice',
                ]);
            }

            CartItem::query()
                ->where('user_id', $userId)
                ->delete();

            return $rental->load('items');
        });
    }

    /**
     * Konfirmasi rental saat dibayar. Karena stok sudah dipotong di createFromCart, 
     * kita hanya perlu ubah status.
     */
    public function confirmRental(Rental $rental, ?int $confirmedBy = null): Rental
    {
        return DB::transaction(function () use ($rental) {
            $rental = Rental::query()
                ->lockForUpdate()
                ->findOrFail($rental->id);

            if ($rental->status !== 'pending_payment') {
                throw ValidationException::withMessages([
                    'rental' => 'Rental tidak bisa dikonfirmasi dari status saat ini.',
                ]);
            }

            $rental->update([
                'status' => 'confirmed',
            ]);

            // [UPDATE] Kirim Email Konfirmasi secara terpusat dari Service (aktif untuk Midtrans & Manual Admin)
            try {
                Mail::to($rental->user->email)->send(new RentalConfirmedMail($rental));
            } catch (\Exception $e) {
                Log::error('Failed to send confirmation email in RentalService: ' . $e->getMessage());
            }

            return $rental->refresh();
        });
    }

    /**
     * Aktivasi rental (barang diserahkan ke pelanggan / Pickup)
     */
    public function activateRental(Rental $rental): Rental
    {
        return DB::transaction(function () use ($rental) {
            $rental = Rental::query()
                ->lockForUpdate()
                ->findOrFail($rental->id);

            if ($rental->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'rental' => 'Rental tidak bisa diaktifkan dari status saat ini.',
                ]);
            }

            $rental->update([
                'status' => 'active',
                'picked_up_at' => now(),
            ]);

            return $rental->refresh();
        });
    }

    /**
     * Barang dikembalikan. Menghitung keterlambatan, denda, dan mengembalikan stok yang bagus.
     */
    public function processReturn(Rental $rental, array $itemsConditions = [], ?int $returnedBy = null): Rental
    {
        return DB::transaction(function () use ($rental, $itemsConditions) {
            $rental = Rental::query()
                ->lockForUpdate()
                ->findOrFail($rental->id);

            if ($rental->status !== 'active') {
                throw ValidationException::withMessages([
                    'rental' => 'Rental tidak bisa diproses return dari status saat ini.',
                ]);
            }

            $rental->load('items.package.items.product');

            $lateDays = 0;
            $latePenalty = 0;
            $damagePenalty = 0;

            // Hitung keterlambatan
            $endDate = Carbon::parse($rental->rental_end)->endOfDay();
            if (now()->isAfter($endDate)) {
                $lateDays = now()->startOfDay()->diffInDays($endDate->startOfDay());
                if ($lateDays > 0) {
                    $dailyRate = $rental->total_rental / max(1, $rental->total_days);
                    $latePenalty = $dailyRate * $lateDays;
                }
            }

            foreach ($rental->items as $item) {
                // Ambil data kondisi dari array input
                $conditionData = $itemsConditions[$item->id] ?? [];
                
                $condition = $conditionData['condition'] ?? 'good';
                $missingQty = (int) ($conditionData['missing_qty'] ?? 0);
                $damagedQty = (int) ($conditionData['damaged_qty'] ?? 0);
                $notes = $conditionData['notes'] ?? null;
                $penalty = (float) ($conditionData['penalty'] ?? 0);

                $damagePenalty += $penalty;

                $item->update([
                    'condition_returned' => $condition,
                    'missing_qty' => $missingQty,
                    'damaged_qty' => $damagedQty,
                    'notes' => $notes,
                ]);

                $goodQty = max(0, $item->quantity - $missingQty - $damagedQty);

                // Kembalikan stok HANYA untuk barang yang bagus
                if ($goodQty > 0) {
                    if ($item->item_type === 'product') {
                        $product = Product::query()
                            ->whereKey($item->product_id)
                            ->lockForUpdate()
                            ->first();

                        if ($product) {
                            $product->increment('stock_available', $goodQty);
                        }
                    } else {
                        $package = $item->package()->with('items.product')->first();
                        if ($package) {
                            foreach ($package->items as $packageItem) {
                                $product = Product::query()
                                    ->whereKey($packageItem->product_id)
                                    ->lockForUpdate()
                                    ->first();

                                if ($product) {
                                    $returnQty = (int) $packageItem->quantity * $goodQty;
                                    $product->increment('stock_available', $returnQty);
                                }
                            }
                        }
                    }
                }
            }

            $totalPenalty = $latePenalty + $damagePenalty;

            $rental->update([
                'status' => 'completed',
                'returned_at' => now(),
                'late_days' => $lateDays,
                'late_penalty' => $latePenalty,
                'damage_penalty' => $damagePenalty,
                'total_penalty' => $totalPenalty,
                'penalty_status' => $totalPenalty > 0 ? 'unpaid' : 'none',
            ]);

            return $rental->refresh();
        });
    }

    /**
     * Batalkan rental jika tidak dibayar, dan kembalikan stok.
     */
    public function cancelRental(Rental $rental): Rental
    {
        return DB::transaction(function () use ($rental) {
            $rental = Rental::query()
                ->lockForUpdate()
                ->findOrFail($rental->id);

            if ($rental->status !== 'pending_payment') {
                throw ValidationException::withMessages([
                    'rental' => 'Rental tidak bisa dibatalkan dari status saat ini.',
                ]);
            }

            $rental->load('items.package.items.product');

            foreach ($rental->items as $item) {
                if ($item->item_type === 'product') {
                    $product = Product::query()
                        ->whereKey($item->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $product->increment('stock_available', (int) $item->quantity);
                } else {
                    $package = $item->package()->with('items.product')->firstOrFail();

                    foreach ($package->items as $packageItem) {
                        $product = Product::query()
                            ->whereKey($packageItem->product_id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        $returnQty = (int) $packageItem->quantity * (int) $item->quantity;
                        $product->increment('stock_available', $returnQty);
                    }
                }
            }

            $rental->update([
                'status' => 'cancelled',
            ]);

            return $rental->refresh();
        });
    }

    public function reorderSuggestions(Rental $rental): array
    {
        $rental->load('items.product', 'items.package.items.product');

        $result = [];

        foreach ($rental->items as $item) {
            if ($item->item_type === 'product') {
                $available = $item->product && (int) $item->product->stock_available >= (int) $item->quantity;

                $result[] = [
                    'type' => 'product',
                    'name' => $item->product_name,
                    'product_id' => $item->product_id,
                    'quantity' => (int) $item->quantity,
                    'available' => $available,
                ];
            } else {
                $package = $item->package;
                $available = true;

                if ($package) {
                    foreach ($package->items as $packageItem) {
                        if (
                            ! $packageItem->product ||
                            (int) $packageItem->product->stock_available < ((int) $packageItem->quantity * (int) $item->quantity)
                        ) {
                            $available = false;
                            break;
                        }
                    }
                } else {
                    $available = false;
                }

                $result[] = [
                    'type' => 'package',
                    'name' => $item->product_name,
                    'package_id' => $item->package_id,
                    'quantity' => (int) $item->quantity,
                    'available' => $available,
                ];
            }
        }

        return $result;
    }
}
