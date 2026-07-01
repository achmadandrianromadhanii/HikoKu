<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();

            $table->string('payment_code')->unique();
            $table->string('method')->default('midtrans');

            $table->string('gateway_transaction_id')->nullable();
            $table->text('snap_token')->nullable();
            $table->text('payment_url')->nullable();

            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['rental_id', 'status'], 'idx_rental_status');
            $table->index('gateway_transaction_id', 'idx_gateway_tx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
