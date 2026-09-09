<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('first_rental_id')->nullable()->after('auto_sk_sent');
            $table->date('first_start_sewa_date')->nullable()->after('first_rental_id');
            $table->string('first_customer_name')->nullable()->after('first_start_sewa_date');
            $table->string('first_sent_as', 32)->nullable()->after('first_customer_name');

            $table->index('first_start_sewa_date');
            $table->index('first_rental_id');
            $table->index('first_sent_as');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['first_start_sewa_date']);
            $table->dropIndex(['first_rental_id']);
            $table->dropIndex(['first_sent_as']);

            $table->dropColumn([
                'first_rental_id',
                'first_start_sewa_date',
                'first_customer_name',
                'first_sent_as',
            ]);
        });
    }
};
