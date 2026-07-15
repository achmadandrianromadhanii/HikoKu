<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPackage extends Model
{
    use HasFactory;

    protected $table = 'product_packages';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_per_day',

        'is_active',
        'is_featured',
        'image_path',
    ];

    /**
     * Cast atribut model.
     */
    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Relasi utama ke tabel package_items.
     * Ini dipertahankan agar tetap kompatibel dengan kode kamu yang sekarang.
     */
    public function packageItems(): HasMany
    {
        return $this->hasMany(PackageItem::class, 'package_id');
    }

    /**
     * Alias relasi supaya lebih enak dipakai di controller baru.
     * Jadi kamu bisa pakai:
     * - $package->packageItems
     * - $package->items
     * Keduanya sama-sama valid.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class, 'package_id');
    }

    /**
     * Relasi many-to-many ke products lewat package_items.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'package_items',
            'package_id',
            'product_id'
        )->withPivot('quantity');
    }

    /**
     * Paket bisa muncul di cart_items.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'package_id');
    }

    /**
     * Paket bisa muncul di rental_items.
     */
    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class, 'package_id');
    }

    /**
     * Scope: hanya paket aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: hanya paket unggulan.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
