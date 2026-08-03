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
            $table->string('branch')->default('ALL')->after('email'); // ALL, JKT, SUB, SMG, DPS, etc.
            $table->string('role')->default('branch_user')->after('branch'); // it_admin, branch_user
            $table->json('menu_permissions')->nullable()->after('role'); // ['dashboard', 'total-stock', 'rental-pairs', 'lor', 'crm']
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['branch', 'role', 'menu_permissions']);
        });
    }
};
