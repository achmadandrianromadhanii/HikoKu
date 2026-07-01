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
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('picked_up_at')->nullable()->after('status');
            $table->timestamp('returned_at')->nullable()->after('picked_up_at');
            $table->integer('late_days')->default(0)->after('grand_total');
            $table->decimal('late_penalty', 12, 2)->default(0)->after('late_days');
            $table->decimal('damage_penalty', 12, 2)->default(0)->after('late_penalty');
            $table->decimal('total_penalty', 12, 2)->default(0)->after('damage_penalty');
            $table->string('penalty_status')->default('none')->after('total_penalty');
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->string('condition_returned')->default('good')->after('subtotal');
            $table->integer('missing_qty')->default(0)->after('condition_returned');
            $table->integer('damaged_qty')->default(0)->after('missing_qty');
            $table->text('notes')->nullable()->after('damaged_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn([
                'picked_up_at',
                'returned_at',
                'late_days',
                'late_penalty',
                'damage_penalty',
                'total_penalty',
                'penalty_status'
            ]);
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropColumn([
                'condition_returned',
                'missing_qty',
                'damaged_qty',
                'notes'
            ]);
        });
    }
};
