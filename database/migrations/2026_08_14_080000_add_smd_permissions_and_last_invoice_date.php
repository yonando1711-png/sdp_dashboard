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
            if (!Schema::hasColumn('items', 'last_invoice_date')) {
                $table->date('last_invoice_date')->nullable()->after('amount_total');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'can_view_smd_last_invoice_date')) {
                $table->boolean('can_view_smd_last_invoice_date')->default(false)->after('can_view_lor_smd');
            }
            if (!Schema::hasColumn('users', 'can_export_lor_smd')) {
                $table->boolean('can_export_lor_smd')->default(false)->after('can_view_smd_last_invoice_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'last_invoice_date')) {
                $table->dropColumn('last_invoice_date');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'can_view_smd_last_invoice_date')) {
                $table->dropColumn('can_view_smd_last_invoice_date');
            }
            if (Schema::hasColumn('users', 'can_export_lor_smd')) {
                $table->dropColumn('can_export_lor_smd');
            }
        });
    }
};
