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
        Schema::create('surat_kuasa_system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('info'); // info, success, warning, error
            $table->string('event_type', 50)->default('system'); // odoo_sync, fast_sync, auto_generate, email_send, doc_generate
            $table->string('lot_number', 100)->nullable();
            $table->string('doc_no', 100)->nullable();
            $table->text('message');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['level', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index('lot_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_kuasa_system_logs');
    }
};
