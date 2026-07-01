<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'package_id',
        'product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ProductPackage::class, 'package_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
