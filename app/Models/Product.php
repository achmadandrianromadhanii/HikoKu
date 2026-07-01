<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_desc',
        'price_per_day',
        'stock_total',
        'stock_available',
        'weight_gram',
        'condition',
        'is_active',
        'is_featured',
        // [UPDATE]: Menghapus 'avg_rating' karena ini adalah sisa kode dari fitur review lama yang sudah tidak dipakai.
        // Menghapusnya akan membersihkan memori dan keamanan mass-assignment.
    ];

    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'stock_total' => 'integer',
            'stock_available' => 'integer',
            'weight_gram' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)
            ->where('is_primary', true);
    }

    public function packageItems(): HasMany
    {
        return $this->hasMany(PackageItem::class);
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductPackage::class,
            'package_items',
            'product_id',
            'package_id'
        )->withPivot('quantity');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }


    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('stock_available', '>', 0);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)
            ->where('is_active', true);
    }
}
