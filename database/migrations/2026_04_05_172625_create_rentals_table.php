<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_code')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('status')->default('pending_payment');

            $table->date('rental_start');
            $table->date('rental_end');
            $table->unsignedInteger('total_days')->default(1);

            $table->decimal('subtotal', 12, 2)->default(0);

            $table->decimal('total_rental', 12, 2)->default(0);
            $table->decimal('deposit_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['rental_start', 'rental_end'], 'idx_date_range');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
