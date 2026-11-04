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
            if (!Schema::hasColumn('users', 'can_view_cadangan_biaya')) {
                $table->boolean('can_view_cadangan_biaya')->default(false)->after('can_view_et_report');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'can_view_cadangan_biaya')) {
                $table->dropColumn('can_view_cadangan_biaya');
            }
        });
    }
};

