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
            $table->string('status')->nullable()->after('rental_id');
        });

        Schema::table('lor_histories', function (Blueprint $table) {
            $table->string('status')->nullable()->after('rental_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lor_histories', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
