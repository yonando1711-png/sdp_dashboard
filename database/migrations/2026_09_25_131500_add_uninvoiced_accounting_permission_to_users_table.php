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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'can_view_uninvoiced_accounting')) {
                $table->boolean('can_view_uninvoiced_accounting')->default(false)->after('can_view_summary_rented_vehicle');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'can_view_uninvoiced_accounting')) {
                $table->dropColumn('can_view_uninvoiced_accounting');
            }
        });
    }
};
