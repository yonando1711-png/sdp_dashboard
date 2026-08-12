<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Tracks units that were ever detected by the SK sync (qty=0, awaiting No.Rangka & No.Mesin)
            // true  → unit stays visible in SK list (even after rangka/mesin is filled, ready to generate SK)
            // false → unit is not in SK tracking (regular item from main sync)
            $table->boolean('surat_kuasa_tracked')->default(false)->after('is_on_hand');
        });

        // Seed: mark all CURRENTLY pending SK units as tracked
        // (qty=0, not vendor rent, empty rangka/mesin)
        DB::table('items')
            ->where('on_hand_quantity', 0)
            ->where(function ($q) {
                $q->whereNull('is_vendor_rent')->orWhere('is_vendor_rent', false);
            })
            ->where(function ($q) {
                $q->whereNull('internal_reference')->orWhere('internal_reference', '');
            })
            ->where(function ($q) {
                $q->whereNull('engine_number')->orWhere('engine_number', '');
            })
            ->update(['surat_kuasa_tracked' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('surat_kuasa_tracked');
        });
    }
};
