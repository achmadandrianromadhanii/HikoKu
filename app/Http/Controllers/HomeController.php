<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Models\ProductPackage;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Menampilkan halaman utama (Welcome / Landing Page).
     * Mengambil data kategori, produk unggulan, paket, dan FAQ yang berstatus aktif.
     */
    public function index(): Response
    {
        // [OPTIMASI VERCEL/LATENCY]: Cache seluruh query berat Landing Page selama 1 jam.
        // Jika ada perubahan data di panel admin, data baru akan muncul maksimal 1 jam,
        // namun kecepatan load halaman (TTFB) bagi user akan instan (dari ~800ms ke ~20ms).
        $categories = Cache::remember('home_categories', now()->addHours(1), function () {
            return Category::query()
                ->where('is_active', true)
                ->withCount('products')
                ->orderBy('sort_order')
                ->take(8)
                ->get();
        });

        // Mengambil 8 produk unggulan (featured) terbaru
        $featuredProducts = Cache::remember('home_featured_products', now()->addHours(1), function () {
            return Product::query()
                ->with(['category:id,name,slug', 'images', 'variants'])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->latest()
                ->take(8)
                ->get();
        });

        // Mengambil 3 paket produk terbaru
        $packages = Cache::remember('home_packages', now()->addHours(1), function () {
            return ProductPackage::query()
                ->where('is_active', true)
                ->latest()
                ->take(3)
                ->get();
        });

        // Mengambil 5 FAQ aktif
        $faqs = Cache::remember('home_faqs', now()->addHours(1), function () {
            return Faq::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->take(5)
                ->get();
        });

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
