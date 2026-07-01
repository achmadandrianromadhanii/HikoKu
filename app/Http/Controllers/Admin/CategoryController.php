<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::withCount('products')
                ->orderBy('sort_order')
                ->paginate(12),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Category::create([
            ...$data,
            'slug' => Str::slug($data['name']),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        CacheService::bustCategories();

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category->update([
            ...$data,
            'slug' => Str::slug($data['name']),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        CacheService::bustCategories();

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        CacheService::bustCategories();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}
