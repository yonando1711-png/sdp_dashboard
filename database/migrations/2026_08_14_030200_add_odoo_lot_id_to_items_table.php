<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add odoo_lot_id to items table.
     * This stores the Odoo integer lot ID so we can track lot renames.
     * When Anna fills in No. Rangka in Odoo, the Odoo lot name changes (e.g.
     * "00161-GRANMAX MB 1.3 4" → "00921-GRANMAX MB 1.3 4"). By storing the
     * Odoo lot ID, Fast Sync can still find and update the correct DB record
     * even after the lot name changes, without losing tracking history.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_lot_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['odoo_lot_id']);
            $table->dropColumn('odoo_lot_id');
        });
    }
};
