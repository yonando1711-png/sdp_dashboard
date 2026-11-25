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
        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('frequency')->default('daily'); // daily, weekly, monthly
            $table->string('time')->default('02:00'); // HH:MM
            $table->unsignedTinyInteger('day_of_week')->nullable()->default(1); // 0 = Sunday, 1 = Monday, etc.
            $table->unsignedTinyInteger('day_of_month')->nullable()->default(1); // 1-31
            $table->boolean('prune_enabled')->default(true);
            $table->unsignedInteger('keep_daily')->default(7);
            $table->unsignedInteger('keep_weekly')->default(4);
            $table->unsignedInteger('keep_monthly')->default(6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_schedules');
    }
};
