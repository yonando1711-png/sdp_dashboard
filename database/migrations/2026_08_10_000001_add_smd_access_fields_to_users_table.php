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
            $table->boolean('can_view_lor_smd')->default(false)->after('role');
            $table->json('allowed_salespersons')->nullable()->after('can_view_lor_smd');
            $table->json('allowed_sales_teams')->nullable()->after('allowed_salespersons');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_view_lor_smd', 'allowed_salespersons', 'allowed_sales_teams']);
        });
    }
};
