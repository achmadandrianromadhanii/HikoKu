<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_id',
        'item_type',
        'product_id',
        'package_id',
        'product_variant_id',
        'product_name',
        'quantity',
        'price_per_day',
        'subtotal',
        'condition_returned',
        'missing_qty',
        'damaged_qty',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'missing_qty' => 'integer',
            'damaged_qty' => 'integer',
            'price_per_day' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ProductPackage::class, 'package_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
