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
            if (!Schema::hasColumn('users', 'can_view_accounting_report')) {
                $table->boolean('can_view_accounting_report')->default(false)->after('can_export_disposal');
            }
            if (!Schema::hasColumn('users', 'can_view_summary_rented_vehicle')) {
                $table->boolean('can_view_summary_rented_vehicle')->default(false)->after('can_view_accounting_report');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'can_view_summary_rented_vehicle')) {
                $table->dropColumn('can_view_summary_rented_vehicle');
            }
            if (Schema::hasColumn('users', 'can_view_accounting_report')) {
                $table->dropColumn('can_view_accounting_report');
            }
        });
    }
};
