<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah discount_amount ke rentals dan drop deposit_total
        Schema::table('rentals', function (Blueprint $table) {
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal');
            $table->dropColumn('deposit_total');
        });

        // 2. Drop deposit_amount dari rental_items
        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropColumn('deposit_amount');
        });

        // 3. Drop deposit_amount dari products
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('deposit_amount');
        });

        // 4. Drop deposit_total dari product_packages
        Schema::table('product_packages', function (Blueprint $table) {
            $table->dropColumn('deposit_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_packages', function (Blueprint $table) {
            $table->decimal('deposit_total', 12, 2)->default(0)->after('price_per_day');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('deposit_amount', 12, 2)->default(0)->after('price_per_day');
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->decimal('deposit_amount', 12, 2)->default(0)->after('price_per_day');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
            $table->decimal('deposit_total', 12, 2)->default(0)->after('total_rental');
        });
    }
};
