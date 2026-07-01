<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::query()
            ->with(['category:id,name,slug', 'images', 'variants'])
            ->active();

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->string('category'));
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('short_desc', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->boolean('available_only')) {
            $query->where('stock_available', '>', 0);
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->string('condition'));
        }

        if ($request->filled('min_price')) {
            $query->where('price_per_day', '>=', (float) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price_per_day', '<=', (float) $request->max_price);
        }

        match ($request->string('sort')->toString()) {
            'price_low' => $query->orderBy('price_per_day'),
            'price_high' => $query->orderByDesc('price_per_day'),
            'rating' => $query->orderByDesc('avg_rating'),
            'latest' => $query->latest(),
            default => $query->latest(),
        };

        return Inertia::render('Catalog/Index', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Category::active()->orderBy('sort_order')->get(['id', 'name', 'slug']),
            'filters' => $request->only([
                'category',
                'search',
                'available_only',
                'condition',
                'min_price',
                'max_price',
                'sort',
            ]),
        ]);
    }

    public function show(string $slug): Response
    {
        // [UPDATE]: Menghapus pemuatan relasi (eager load) 'reviews.user:id,name'
        // untuk mencegah error "Call to undefined relationship [reviews]".
        $product = Product::with([
            'category:id,name,slug',
            'images',
            'variants',
        ])->where('slug', $slug)->active()->firstOrFail();

        $relatedProducts = Product::with(['category:id,name,slug', 'images', 'variants'])
            ->active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        return Inertia::render('Products/Show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
