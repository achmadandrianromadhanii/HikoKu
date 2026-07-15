<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackageItem;
use App\Models\Product;
use App\Models\ProductPackage;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class PackageController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->string('search')->toString(),
            'active_only' => (bool) $request->boolean('active_only'),
        ];

        $query = ProductPackage::query()
            ->with(['items.product:id,name'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['active_only'], function ($query) {
                $query->where('is_active', true);
            })
            ->latest('id');

        $paginator = $query->paginate(10)->withQueryString();

        $packages = [
            'data' => collect($paginator->items())
                ->map(fn(ProductPackage $package) => $this->transformPackageForIndex($package))
                ->values()
                ->all(),
            'links' => $paginator->linkCollection()->values()->all(),
        ];

        return Inertia::render('Admin/Packages/Index', [
            'packages' => $packages,
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => $filters,
            'stats' => [
                'total' => ProductPackage::query()->count(),
                'active' => ProductPackage::query()->where('is_active', true)->count(),
                'featured' => ProductPackage::query()->where('is_featured', true)->count(),
                'total_items' => PackageItem::query()->sum('quantity'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Packages/Create', [
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->ensureUniquePackageItems($data['items']);

        DB::transaction(function () use ($data, $request) {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $this->storeProcessedImage($request);
            }

            $package = ProductPackage::create([
                'name' => $data['name'],
                'slug' => $this->generateUniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'price_per_day' => $data['price_per_day'],

                'image_path' => $imagePath,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
            ]);

            $this->syncPackageItems($package, $data['items']);
        });

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket berhasil ditambahkan.');
    }

    public function show(string $id): RedirectResponse
    {
        $package = ProductPackage::query()->findOrFail($id);

        return redirect()->route('admin.packages.edit', $package->id);
    }

    public function edit(string $id): Response
    {
        $package = ProductPackage::query()
            ->with(['items.product:id,name'])
            ->findOrFail($id);

        return Inertia::render('Admin/Packages/Edit', [
            'packageItem' => $package,
            'products' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $package = ProductPackage::query()
            ->with('items')
            ->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->ensureUniquePackageItems($data['items']);

        DB::transaction(function () use ($package, $data, $request) {
            $imagePath = $package->image_path;
            if ($request->hasFile('image')) {
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = $this->storeProcessedImage($request);
            }

            $package->update([
                'name' => $data['name'],
                'slug' => $package->slug ?: $this->generateUniqueSlug($data['name'], $package->id),
                'description' => $data['description'] ?? null,
                'price_per_day' => $data['price_per_day'],

                'image_path' => $imagePath,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
            ]);

            $this->syncPackageItems($package, $data['items']);
        });

        return redirect()
            ->route('admin.packages.index')
            ->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $package = ProductPackage::query()->findOrFail($id);

        try {
            DB::transaction(function () use ($package) {
                if ($package->image_path && Storage::disk('public')->exists($package->image_path)) {
                    Storage::disk('public')->delete($package->image_path);
                }
                $package->items()->delete();
                $package->delete();
            });

            return redirect()
                ->route('admin.packages.index')
                ->with('success', 'Paket berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::channel('app_error')->error('AdminPackageController::destroy', [
                'error' => $e->getMessage(),
                'package_id' => $package->id,
            ]);

            return back()->with('error', 'Gagal menghapus paket.');
        }
    }

    protected function transformPackageForIndex(ProductPackage $package): array
    {
        $itemsPreview = $package->items
            ->map(function ($item) {
                $productName = $item->product?->name ?? 'Produk';
                return $productName . ' x' . (int) $item->quantity;
            })
            ->implode(', ');

        return [
            'id' => $package->id,
            'name' => $package->name,
            'slug' => $package->slug,
            'description' => $package->description,
            'primary_image_url' => $package->image_path ? asset('storage/' . ltrim($package->image_path, '/')) : null,
            'price_label' => 'Rp ' . number_format((float) $package->price_per_day, 0, ',', '.'),

            'is_active' => (bool) $package->is_active,
            'is_featured' => (bool) $package->is_featured,
            'items_preview' => $itemsPreview !== '' ? $itemsPreview : '-',
            'items' => $package->items->map(fn($item) => [
                'product_id' => $item->product_id,
                'quantity' => (int) $item->quantity,
                'product' => [
                    'name' => $item->product?->name,
                ],
            ])->values()->all(),
        ];
    }

    protected function syncPackageItems(ProductPackage $package, array $items): void
    {
        $package->items()->delete();

        foreach ($items as $index => $item) {
            $package->items()->create([
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
            ]);
        }
    }

    protected function ensureUniquePackageItems(array $items): void
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values();

        if ($productIds->count() !== $productIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Produk dalam satu paket tidak boleh duplikat.',
            ]);
        }
    }

    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : Str::lower(Str::random(8));

        $counter = 1;

        while (
            ProductPackage::query()
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // Komentar: Memproses foto paket agar beresolusi tinggi (HD) dan tajam, namun tetap dalam format WebP
    // Resolusi dinaikkan ke 1200px dan kualitas ke 85% agar gambar sangat jernih di sisi User dan Guest.
    protected function storeProcessedImage(Request $request): string
    {
        $manager = new ImageManager(new Driver());

        $image = $manager->read($request->file('image')->getRealPath())
            ->scaleDown(width: 1200);

        $filename = 'packages/' . Str::uuid() . '.webp';

        Storage::disk('public')->put($filename, (string) $image->toWebp(85));

        return $filename;
    }
}
