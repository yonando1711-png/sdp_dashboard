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
        Schema::table('items', function (Blueprint $table) {
            $table->index(['branch', 'in_stock'], 'idx_items_branch_in_stock');
            $table->index(['branch', 'location'], 'idx_items_branch_location');
            $table->index('current_customer', 'idx_items_current_customer');
            $table->index(['rental_id', 'rental_id_count'], 'idx_items_rental_id_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_branch_in_stock');
            $table->dropIndex('idx_items_branch_location');
            $table->dropIndex('idx_items_current_customer');
            $table->dropIndex('idx_items_rental_id_count');
        });
    }
};
