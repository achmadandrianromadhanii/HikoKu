<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WishlistController extends Controller
{
    public function __construct(
        protected WishlistService $wishlistService
    ) {}

    /**
     * Menampilkan halaman daftar wishlist pengguna.
     */
    public function index()
    {
        // Menggunakan whereHas untuk memastikan tidak terjadi N+1 query yang lambat
        $products = Product::query()
            ->whereHas('wishlists', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->with(['category:id,name,slug', 'images'])
            ->get();

        return Inertia::render('Wishlist/Index', [
            'products' => $products,
        ]);
    }

    /**
     * Menambahkan atau menghapus produk dari wishlist (toggle).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $result = $this->wishlistService->toggle(
            $request->user()->id,
            (int) $data['product_id']
        );

        return back()->with([
            'success' => $result['wishlisted']
                ? 'Produk berhasil ditambahkan ke wishlist.'
                : 'Produk berhasil dihapus dari wishlist.',
            'wishlist' => [
                'product_id' => (int) $data['product_id'],
                'wishlisted' => (bool) $result['wishlisted'],
            ],
        ]);
    }

    /**
     * Menghapus secara eksplisit sebuah produk dari wishlist pengguna.
     */
    public function destroy(Product $product): RedirectResponse
    {
        Wishlist::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->delete();

        return back()->with('success', 'Produk dihapus dari wishlist.');
    }
}
