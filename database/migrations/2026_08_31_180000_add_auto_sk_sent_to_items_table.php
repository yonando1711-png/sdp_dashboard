<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Timestamp when the auto-SK was successfully generated & emailed
            // NULL = not yet auto-processed, non-null = already auto-sent (prevents re-processing)
            $table->timestamp('auto_sk_sent')->nullable()->after('vehicle_category');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('auto_sk_sent');
        });
    }
};
