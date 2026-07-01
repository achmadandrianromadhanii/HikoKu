<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Models\ProductPackage;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Menampilkan halaman utama (Welcome / Landing Page).
     * Mengambil data kategori, produk unggulan, paket, dan FAQ yang berstatus aktif.
     */
    public function index(): Response
    {
        // Mengambil 8 kategori aktif dengan hitungan jumlah produk
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order')
            ->take(8)
            ->get();

        // Mengambil 8 produk unggulan (featured) terbaru
        $featuredProducts = Product::query()
            ->with(['category:id,name,slug', 'images', 'variants'])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->latest()
            ->take(8)
            ->get();

        // Mengambil 3 paket produk terbaru
        $packages = ProductPackage::query()
            ->where('is_active', true)
            ->latest()
            ->take(3)
            ->get();

        // Mengambil 5 FAQ aktif
        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->take(5)
            ->get();

        return Inertia::render('Welcome/Index', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'packages' => $packages,
            'faqs' => $faqs,
        ]);
    }
}
