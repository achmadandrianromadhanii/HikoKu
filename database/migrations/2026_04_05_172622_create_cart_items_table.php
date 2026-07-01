<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('item_type'); // product | package
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('product_packages')->nullOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index(['user_id', 'expires_at'], 'idx_user_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
