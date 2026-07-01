<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();

            $table->string('item_type');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('product_packages')->nullOnDelete();

            $table->string('product_name');
            $table->unsignedInteger('quantity')->default(1);

            $table->decimal('price_per_day', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);

            $table->timestamps();

            $table->index(['product_id', 'rental_id'], 'idx_product_rental');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_items');
    }
};
