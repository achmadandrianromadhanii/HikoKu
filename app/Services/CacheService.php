<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    public static function bustProducts(): void
    {
        Cache::forget('products.featured');
        Cache::forget('products.all');
        Cache::forget('home_featured_products');
    }

    public static function bustPackages(): void
    {
        Cache::forget('home_packages');
    }

    public static function bustCategories(): void
    {
        Cache::forget('categories.active');
    }

    public static function bustSettings(): void
    {
        Cache::forget('settings.public');
    }

    public static function getFeaturedProducts()
    {
        return Cache::remember('products.featured', 600, function () {
            return \App\Models\Product::with(['category:id,name,slug', 'images'])
                ->featured()
                ->latest()
                ->take(8)
                ->get();
        });
    }

    public static function getCategories()
    {
        return Cache::remember('categories.active', 600, function () {
            return Category::active()
                ->withCount('products')
                ->orderBy('sort_order')
                ->get();
        });
    }

    public static function getSettings()
    {
        return Cache::remember('settings.public', 600, function () {
            return [
                'app_name' => 'Hiko',
                'app_tagline' => 'Outdoor Equipment Rental',
                'store_address' => 'Jl. Merdeka No.123, Bandung',
                'store_phone' => '081234567890',
                'store_email' => 'hello@hiko.com',
                'wa_number' => '081234567890',
                'wa_greeting' => 'Halo, saya ingin bertanya tentang...',
                'instagram_url' => 'https://instagram.com/hiko',
                'opening_hours' => 'Senin - Minggu: 08:00 - 21:00',
            ];
        });
    }
}
