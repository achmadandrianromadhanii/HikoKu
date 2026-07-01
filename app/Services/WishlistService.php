<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Wishlist;

class WishlistService
{
    public function toggle(int $userId, int $productId): array
    {
        Product::findOrFail($productId);

        $wishlist = Wishlist::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($wishlist) {
            $wishlist->delete();

            return ['wishlisted' => false];
        }

        Wishlist::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'created_at' => now(),
        ]);

        return ['wishlisted' => true];
    }
}
