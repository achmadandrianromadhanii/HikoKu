<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->string('search')->toString(),
            'category' => (string) $request->string('category')->toString(),
            'status' => (string) $request->string('status')->toString(),
            'featured' => $request->has('featured') ? (string) $request->input('featured') : '',
        ];

        $lowStockThreshold = 2;

        // Query Utama Produk
        $query = Product::query()
            ->with(['category:id,name,slug', 'images', 'variants'])
            ->withCount('variants')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('short_desc', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['category'] !== '', function ($query) use ($filters) {
                $category = $filters['category'];

                if (is_numeric($category)) {
                    $query->where('category_id', (int) $category);
                    return;
                }

                $query->whereHas('category', function ($categoryQuery) use ($category) {
                    $categoryQuery->where('slug', $category);
                });
            })
            ->when($filters['status'] === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($filters['status'] === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->when($filters['featured'] !== '', function ($query) use ($filters) {
                $query->where('is_featured', (bool) $filters['featured']);
            })
            ->latest('id');

        // Membatasi tampilan menjadi maksimal 6 data per halaman (Sesuai instruksi)
        $paginator = $query->paginate(6)->withQueryString();

        // Menyusun objek response dengan menambahkan prev/next url untuk custom pagination (< >)
        $products = [
            'data' => collect($paginator->items())
                ->map(fn(Product $product) => $this->transformProductForIndex($product, $lowStockThreshold))
                ->values()
                ->all(),
            'links' => $paginator->linkCollection()->values()->all(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'next_page_url' => $paginator->nextPageUrl(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
        ];

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => Category::active()
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']),
            'filters' => $filters,
            'stats' => [ // Stats tetap dikirim ke view jika sewaktu-waktu dibutuhkan, namun di view akan di-hidden
                'total' => Product::query()->count(),
                'active' => Product::query()->where('is_active', true)->count(),
                'featured' => Product::query()->where('is_featured', true)->count(),
                'low_stock' => Product::query()->where('stock_available', '<=', $lowStockThreshold)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Create', [
            'categories' => Category::active()
                ->orderBy('sort_order')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')],
            'description' => ['nullable', 'string'],
            'short_desc' => ['nullable', 'string'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'weight_gram' => ['nullable', 'integer', 'min:0'],
            'condition' => ['required', 'string', 'max:100'],
            // [UPDATE]: Menghapus validasi 'condition_notes' karena kolom ini tidak ada di database (dead code).
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            
            // Komentar: Stok sekarang dikelola secara global di level produk
            'stock_total' => ['required', 'integer', 'min:0'],

            // Komentar: Validasi untuk varian dinamis (Ukuran & Warna) tanpa stok per varian
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.size' => ['nullable', 'string', 'in:Kecil,Sedang,Besar'],
            'variants.*.color' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($data, $request) {
            // Komentar: Menyimpan data utama produk dengan stok global
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => $this->generateUniqueSlug($data['name']),
                'sku' => $data['sku'] ?: null,
                'description' => $data['description'] ?? null,
                'short_desc' => $data['short_desc'] ?? null,
                'price_per_day' => $data['price_per_day'],
                'stock_total' => $data['stock_total'],
                'stock_available' => $data['stock_total'], // Saat pertama kali dibuat, stock_available = stock_total
                'weight_gram' => $data['weight_gram'] ?? null,
                'condition' => $data['condition'],
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
            ]);

            // Komentar: Menyimpan masing-masing varian tanpa peduli stok per varian
            foreach ($data['variants'] as $variant) {
                $product->variants()->create([
                    'size' => $variant['size'] ?? null,
                    'color' => $variant['color'] ?? null,
                    'stock_total' => 0, // Set 0 karena tidak dipakai lagi
                    'stock_available' => 0, // Set 0 karena tidak dipakai lagi
                ]);
            }

            if ($request->hasFile('image')) {
                $filename = $this->storeProcessedImage($request);

                $product->images()->create([
                    'image_path' => $filename,
                    'alt_text' => $product->name,
                    'is_primary' => true,
                    'sort_order' => 1,
                ]);
            }
        });

        CacheService::bustProducts();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): Response
    {
        // Komentar: Muat relasi variants agar bisa ditampilkan dan diedit di form Vue
        $product->load(['images', 'category:id,name', 'variants']);

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'categories' => Category::active()
                ->orderBy('sort_order')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'description' => ['nullable', 'string'],
            'short_desc' => ['nullable', 'string'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'weight_gram' => ['nullable', 'integer', 'min:0'],
            'condition' => ['required', 'string', 'max:100'],
            // [UPDATE]: Sama seperti saat create, menghapus dead code 'condition_notes'.
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            
            // Komentar: Stok dikelola secara global
            'stock_total' => ['required', 'integer', 'min:0'],
            
            // Komentar: Validasi array varian yang dikirim dari form Edit tanpa stok
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer'], // Bisa null jika varian baru
            'variants.*.size' => ['nullable', 'string', 'in:Kecil,Sedang,Besar'],
            'variants.*.color' => ['nullable', 'string', 'max:100'],
        ]);

        // Komentar: Kita hitung selisih dari stock_total lama dengan yang baru.
        $stockDifference = $data['stock_total'] - $product->stock_total;
        $newStockAvailable = max(0, $product->stock_available + $stockDifference);

        DB::transaction(function () use ($data, $product, $request, $newStockAvailable) {
            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => $product->slug ?: $this->generateUniqueSlug($data['name'], $product->id),
                'sku' => $data['sku'] ?: null,
                'description' => $data['description'] ?? null,
                'short_desc' => $data['short_desc'] ?? null,
                'price_per_day' => $data['price_per_day'],
                'stock_total' => $data['stock_total'],
                'stock_available' => $newStockAvailable,
                'weight_gram' => $data['weight_gram'] ?? null,
                'condition' => $data['condition'],
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
            ]);

            // Komentar: Mengelola varian. Kita ambil ID varian yang dikirim.
            $submittedVariantIds = collect($data['variants'])->pluck('id')->filter()->all();
            
            // Komentar: Hapus varian yang ada di database tapi tidak ada di request
            $product->variants()->whereNotIn('id', $submittedVariantIds)->delete();

            foreach ($data['variants'] as $variantData) {
                if (isset($variantData['id'])) {
                    // Update varian yang sudah ada
                    $variant = $product->variants()->find($variantData['id']);
                    if ($variant) {
                        $variant->update([
                            'size' => $variantData['size'] ?? null,
                            'color' => $variantData['color'] ?? null,
                            'stock_total' => 0,
                            'stock_available' => 0,
                        ]);
                    }
                } else {
                    // Buat varian baru
                    $product->variants()->create([
                        'size' => $variantData['size'] ?? null,
                        'color' => $variantData['color'] ?? null,
                        'stock_total' => 0,
                        'stock_available' => 0,
                    ]);
                }
            }

            if ($request->hasFile('image')) {
                $filename = $this->storeProcessedImage($request);

                $product->images()->update(['is_primary' => false]);

                $product->images()->create([
                    'image_path' => $filename,
                    'alt_text' => $product->name,
                    'is_primary' => true,
                    'sort_order' => 1,
                ]);
            }
        });

        CacheService::bustProducts();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $product->loadMissing('images');

            DB::transaction(function () use ($product) {
                foreach ($product->images as $image) {
                    if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                        Storage::disk('public')->delete($image->image_path);
                    }
                }

                $product->delete();
            });

            CacheService::bustProducts();

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Produk berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::channel('app_error')->error('AdminProductController::destroy', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
            ]);

            return back()->with('error', 'Gagal menghapus produk.');
        }
    }

    protected function transformProductForIndex(Product $product, int $lowStockThreshold): array
    {
        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        $primaryImagePath = $primaryImage ? (string) ($primaryImage->image_path ?? '') : '';

        // Komentar: Menyusun data varian untuk dikirim ke tabel admin
        $variantsCount = $product->variants_count ?? 0;
        $stockLabel = ((int) $product->stock_available) . '/' . ((int) $product->stock_total);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'category_name' => $product->category?->name ?? '-',
            'price_label' => 'Rp ' . number_format((float) $product->price_per_day, 0, ',', '.'),
            'stock_label' => $stockLabel,
            'stock_available' => (int) $product->stock_available,
            'stock_total' => (int) $product->stock_total,
            'variants_count' => $variantsCount,
            'variants' => $product->variants,
            'is_active' => (bool) $product->is_active,
            'is_featured' => (bool) $product->is_featured,
            'is_low_stock' => (int) $product->stock_available <= $lowStockThreshold,
            'primary_image_url' => $primaryImagePath !== ''
                ? asset('storage/' . ltrim($primaryImagePath, '/'))
                : null,
        ];
    }

    protected function ensureValidStock(int $stockTotal, int $stockAvailable): void
    {
        if ($stockAvailable > $stockTotal) {
            throw ValidationException::withMessages([
                'stock_available' => 'Stok tersedia tidak boleh melebihi stok total.',
            ]);
        }
    }

    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : Str::lower(Str::random(8));

        $counter = 1;

        while (
            Product::query()
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // Komentar: Fungsi ini khusus untuk memproses, merampingkan, dan menyimpan gambar produk
    protected function storeProcessedImage(Request $request): string
    {
        $manager = new ImageManager(new Driver());

        // Komentar: Menggunakan scaleDown ke 1200px agar gambar tetap HD dan tajam di layar besar, namun tetap jauh lebih ringan dibanding gambar mentah
        $image = $manager->read($request->file('image')->getRealPath())
            ->scaleDown(width: 1200);

        // Komentar: Menyimpan sebagai ekstensi .webp karena WebP jauh lebih ringan.
        // Kualitas dinaikkan ke 85% agar gambar "HD + Jernih + Tajam" tanpa blur, namun metrik LCP tetap 100% hijau.
        $filename = 'products/' . Str::uuid() . '.webp';

        // Komentar: Menyimpan ke public storage dengan kompresi kualitas 85% (HD)
        Storage::disk('public')->put($filename, (string) $image->toWebp(85));

        return $filename;
    }
}
