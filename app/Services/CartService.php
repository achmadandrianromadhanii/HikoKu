<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductPackage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Menambahkan item ke keranjang.
     * Tidak lagi menyimpan tanggal sewa di dalam item keranjang.
     */
    public function addItem(
        int $userId,
        string $itemType,
        ?int $productId,
        ?int $packageId,
        int $quantity,
        ?int $productVariantId = null,
        ?string $notes = null
    ): CartItem {
        if (! in_array($itemType, ['product', 'package'], true)) {
            throw ValidationException::withMessages([
                'item_type' => 'Tipe item tidak valid.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah minimal 1.',
            ]);
        }

        if ($itemType === 'product' && empty($productId)) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk wajib dipilih.',
            ]);
        }

        if ($itemType === 'package' && empty($packageId)) {
            throw ValidationException::withMessages([
                'package_id' => 'Paket wajib dipilih.',
            ]);
        }

        // Kedaluwarsa cart default 4 jam, tanpa setting di database
        $expiresAt = now()->addHours(4);

        return DB::transaction(function () use (
            $userId,
            $itemType,
            $productId,
            $packageId,
            $quantity,
            $expiresAt,
            $productVariantId,
            $notes
        ) {
            $existing = CartItem::query()
                ->where('user_id', $userId)
                ->where('item_type', $itemType)
                ->where('product_id', $productId)
                ->where('package_id', $packageId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            $finalQuantity = ($existing?->quantity ?? 0) + $quantity;

            if ($itemType === 'product') {
                $product = Product::active()->findOrFail($productId);
                
                if ($productVariantId) {
                    $variant = $product->variants()->findOrFail($productVariantId);
                    // Komentar: Menggunakan stok produk karena stok tidak lagi dipisah per varian
                    $availableStock = max(0, (int) $product->stock_available);
                } else {
                    $availableStock = max(0, (int) $product->stock_available);
                }

                if ($availableStock < 1) {
                    throw ValidationException::withMessages([
                        'product_id' => 'Produk/Varian sedang tidak tersedia.',
                    ]);
                }

                if ($finalQuantity > $availableStock) {
                    throw ValidationException::withMessages([
                        'quantity' => "Stok tidak mencukupi. Tersedia {$availableStock} unit.",
                    ]);
                }
            }

            if ($itemType === 'package') {
                $package = ProductPackage::active()
                    ->with(['items.product'])
                    ->findOrFail($packageId);

                $availablePackageStock = $this->getAvailablePackageStock($package);

                if ($availablePackageStock < 1) {
                    throw ValidationException::withMessages([
                        'package_id' => 'Paket sedang tidak tersedia.',
                    ]);
                }

                if ($finalQuantity > $availablePackageStock) {
                    throw ValidationException::withMessages([
                        'quantity' => "Stok paket {$package->name} tidak mencukupi. Tersedia {$availablePackageStock} paket.",
                    ]);
                }
            }

            if ($existing) {
                $existing->update([
                    'quantity' => $finalQuantity,
                    'expires_at' => $expiresAt,
                ]);

                return $existing->refresh();
            }

            return CartItem::create([
                'user_id' => $userId,
                'item_type' => $itemType,
                'product_id' => $productId,
                'package_id' => $packageId,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'notes' => $notes,
                'expires_at' => $expiresAt,
            ]);
        });
    }

    /**
     * Memperbarui jumlah item dalam keranjang.
     */
    public function updateQty(CartItem $cartItem, int $quantity): CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah minimal 1.',
            ]);
        }

        if ($cartItem->item_type === 'product') {
            $product = Product::active()->findOrFail($cartItem->product_id);
            
            if ($cartItem->product_variant_id) {
                $variant = $product->variants()->findOrFail($cartItem->product_variant_id);
                // Komentar: Menggunakan stok produk karena stok tidak lagi dipisah per varian
                $availableStock = max(0, (int) $product->stock_available);
            } else {
                $availableStock = max(0, (int) $product->stock_available);
            }

            if ($availableStock < 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'Produk/Varian sedang tidak tersedia.',
                ]);
            }

            if ($quantity > $availableStock) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok tidak mencukupi. Tersedia {$availableStock} unit.",
                ]);
            }
        }

        if ($cartItem->item_type === 'package') {
            $package = ProductPackage::active()
                ->with(['items.product'])
                ->findOrFail($cartItem->package_id);

            $availablePackageStock = $this->getAvailablePackageStock($package);

            if ($availablePackageStock < 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'Paket sedang tidak tersedia.',
                ]);
            }

            if ($quantity > $availablePackageStock) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok paket {$package->name} tidak mencukupi. Tersedia {$availablePackageStock} paket.",
                ]);
            }
        }

        $cartItem->update([
            'quantity' => $quantity,
        ]);

        return $cartItem->refresh();
    }

    /**
     * Menghapus item dari keranjang.
     */
    public function removeItem(CartItem $cartItem): void
    {
        $cartItem->delete();
    }

    /**
     * Mendapatkan semua item aktif milik user tertentu.
     */
    public function getActiveItems(int $userId)
    {
        return CartItem::query()
            ->with(['product.images', 'package.items.product', 'variant'])
            ->where('user_id', $userId)
            ->active()
            ->get();
    }

    /**
     * Memastikan tidak ada item kadaluwarsa saat checkout.
     */
    public function ensureNoExpiredItems(int $userId): void
    {
        $hasExpired = CartItem::query()
            ->where('user_id', $userId)
            ->expired()
            ->exists();

        if ($hasExpired) {
            throw ValidationException::withMessages([
                'cart' => 'Ada item keranjang yang sudah kedaluwarsa.',
            ]);
        }
    }

    /**
     * Menghitung ketersediaan stok untuk sebuah paket berdasarkan produk di dalamnya.
     */
    protected function getAvailablePackageStock(ProductPackage $package): int
    {
        $package->loadMissing(['items.product']);

        if ($package->items->isEmpty()) {
            return 0;
        }

        $possibleStocks = [];

        foreach ($package->items as $item) {
            $requiredQty = max(1, (int) $item->quantity);
            $product = $item->product;

            if (! $product || ! $product->is_active) {
                return 0;
            }

            $productStock = max(0, (int) $product->stock_available);
            $possibleStocks[] = (int) floor($productStock / $requiredQty);
        }

        return max(0, min($possibleStocks));
    }
}
