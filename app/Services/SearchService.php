<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SearchService
{
    public function autocomplete(string $term)
    {
        $term = trim($term);

        // Komentar: Logika pencarian diubah. Sekarang cukup 1 huruf (misal: "K") untuk memicu pencarian.
        if ($term === '') {
            return collect();
        }

        $cacheKey = 'search:' . md5($term);

        return Cache::remember($cacheKey, 60, function () use ($term) {
            return Product::query()
                ->with(['category:id,name,slug', 'images'])
                ->active()
                ->selectRaw("
                    products.*,
                    (
                        CASE
                            WHEN name LIKE ? THEN 100
                            WHEN name LIKE ? THEN 70
                            WHEN short_desc LIKE ? THEN 40
                            WHEN description LIKE ? THEN 20
                            ELSE 0
                        END
                    ) as relevance
                ", [
                    $term . '%',
                    '%' . $term . '%',
                    '%' . $term . '%',
                    '%' . $term . '%',
                ])
                ->where(function ($query) use ($term) {
                    $query->where('name', 'like', '%' . $term . '%')
                        ->orWhere('short_desc', 'like', '%' . $term . '%')
                        ->orWhere('description', 'like', '%' . $term . '%');
                })
                ->orderByDesc('relevance')
                ->limit(8)
                ->get()
                ->map(function ($product) use ($term) {
                    $product->highlighted_name = preg_replace(
                        '/' . preg_quote($term, '/') . '/i',
                        '<mark>$0</mark>',
                        e($product->name)
                    );

                    return $product;
                });
        });
    }
}
