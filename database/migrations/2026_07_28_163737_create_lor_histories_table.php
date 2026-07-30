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
        Schema::create('lor_histories', function (Blueprint $table) {
            $table->id();
            $table->string('rental_id')->nullable()->index();
            $table->string('contract_ref')->nullable();
            $table->string('product')->nullable();
            $table->string('lot_number')->nullable()->index();
            $table->string('year')->nullable();
            $table->string('city')->nullable();
            $table->string('current_customer')->nullable();
            $table->string('po')->nullable();
            $table->date('actual_start_rental')->nullable();
            $table->date('actual_end_rental')->nullable();
            $table->string('price')->nullable();
            $table->string('driver')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lor_histories');
    }
};
