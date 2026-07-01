<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Cart/Index', [
            'items' => $this->cartService->getActiveItems($request->user()->id),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_type' => ['required', 'in:product,package'],
            'product_id' => ['nullable', 'exists:products,id'],
            'package_id' => ['nullable', 'exists:product_packages,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'notes' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->cartService->addItem(
            userId: $request->user()->id,
            itemType: $data['item_type'],
            productId: $data['product_id'] ?? null,
            packageId: $data['package_id'] ?? null,
            quantity: $data['quantity'],
            productVariantId: $data['product_variant_id'] ?? null,
            notes: $data['notes'] ?? null
        );

        return back()->with('success', 'Item berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->cartService->updateQty($cartItem, $data['quantity']);

        return back()->with('success', 'Jumlah item diperbarui.');
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);

        $this->cartService->removeItem($cartItem);

        return back()->with('success', 'Item dihapus dari keranjang.');
    }
}
