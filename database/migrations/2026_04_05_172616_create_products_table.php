<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('short_desc')->nullable();

            $table->decimal('price_per_day', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);

            $table->unsignedInteger('stock_total')->default(0);
            $table->unsignedInteger('stock_available')->default(0);

            $table->unsignedInteger('weight_gram')->nullable();
            $table->string('condition')->default('good');

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);


            $table->timestamps();

            $table->index(['category_id', 'is_active'], 'idx_category_active');
            $table->index(['is_featured', 'is_active'], 'idx_featured');
            $table->index('stock_available', 'idx_stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
