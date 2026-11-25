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
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('size'); // in bytes
            $table->string('remark')->nullable();
            $table->string('created_by')->default('System/Scheduler');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
