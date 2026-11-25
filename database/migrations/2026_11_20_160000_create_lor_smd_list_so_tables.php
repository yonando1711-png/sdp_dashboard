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
        // 1. Add granular permission column to users table if not exists
        if (!Schema::hasColumn('users', 'can_view_lor_list_so')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('can_view_lor_list_so')->default(false)->after('can_view_cadangan_biaya');
            });
        }

        // 2. Create Print Batches / Logs table
        if (!Schema::hasTable('lor_smd_so_batches')) {
            Schema::create('lor_smd_so_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_no')->unique(); // e.g. BATCH-20261120-001
                $table->string('sales_team')->nullable()->index();
                $table->unsignedBigInteger('printed_by_id')->nullable();
                $table->string('printed_by_name')->nullable();
                $table->timestamp('printed_at')->index();
                $table->integer('total_so')->default(0);
                $table->integer('print_count')->default(1);
                $table->string('pdf_path')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('lor_smd_so_batches', 'print_count')) {
                Schema::table('lor_smd_so_batches', function (Blueprint $table) {
                    $table->integer('print_count')->default(1);
                });
            }
        }

        // 3. Create SO Movement Queue & Archive table
        if (!Schema::hasTable('lor_smd_so_items')) {
            Schema::create('lor_smd_so_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('batch_id')->nullable()->index();
                $table->string('so_number')->index();
                $table->string('rental_type')->default('SUBSCRIPTION')->index(); // SUBSCRIPTION, REGULER
                $table->string('transaction_type')->index(); // RESERVED, TERMINATE, EXTEND, UPDATE_PRICE, SWITCH_CUSTOMER
                $table->string('sales_team')->nullable()->index();
                $table->string('salesperson')->nullable()->index();
                $table->string('customer_code')->nullable();
                $table->string('customer_name')->nullable()->index();
                $table->string('duration_months')->nullable();
                $table->date('order_date')->nullable()->index();
                $table->date('start_date')->nullable()->index();
                $table->date('end_date')->nullable();
                $table->string('product')->nullable();
                $table->string('reserved_lot')->nullable()->index(); // Plate number / chassis
                $table->string('rbo')->nullable();
                $table->string('previous_so')->nullable();
                $table->string('area')->nullable();
                $table->decimal('duration_price', 15, 2)->nullable();
                $table->decimal('total_price', 15, 2)->nullable();
                $table->date('first_invoice_date')->nullable();
                $table->decimal('komisi', 15, 2)->nullable();
                $table->string('opsi_beli')->nullable();
                $table->text('tags')->nullable();
                $table->string('source_doc')->nullable();
                
                // Diff / Historical columns
                $table->date('actual_end_rental_date')->nullable();
                $table->date('previous_end_date')->nullable();
                $table->decimal('previous_duration_price', 15, 2)->nullable();
                $table->string('previous_customer')->nullable();
                $table->date('previous_end_date_cust')->nullable();

                // Status & Tracking
                $table->boolean('is_printed')->default(false)->index();
                $table->text('raw_odoo_data')->nullable();
                $table->timestamps();

                // Foreign key constraint to batch
                $table->foreign('batch_id')
                      ->references('id')
                      ->on('lor_smd_so_batches')
                      ->onDelete('set null');
            });
        } else {
            if (!Schema::hasColumn('lor_smd_so_items', 'order_date')) {
                Schema::table('lor_smd_so_items', function (Blueprint $table) {
                    $table->date('order_date')->nullable()->after('duration_months');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lor_smd_so_items');
        Schema::dropIfExists('lor_smd_so_batches');

        if (Schema::hasColumn('users', 'can_view_lor_list_so')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('can_view_lor_list_so');
            });
        }
    }
};
