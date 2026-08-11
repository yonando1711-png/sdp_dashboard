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
        Schema::create('surat_kuasa_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('doc_no');
            $table->string('lot_number')->nullable();
            $table->string('product')->nullable();
            $table->string('customer')->nullable();
            $table->string('penerima_nama')->nullable();
            $table->string('penerima_alamat')->nullable();
            $table->string('jenis_model')->default('Mobil Barang');
            $table->string('warna')->nullable();
            $table->string('tahun')->nullable();
            $table->string('no_rangka')->nullable();
            $table->string('no_mesin')->nullable();
            $table->string('print_date')->nullable();
            $table->string('action_type')->default('word'); // 'word' or 'email'
            $table->string('recipient_email')->nullable();
            $table->unsignedBigInteger('generated_by_id')->nullable();
            $table->string('generated_by_name')->nullable();
            $table->timestamps();

            $table->index('item_id');
            $table->index('lot_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_kuasa_logs');
    }
};
