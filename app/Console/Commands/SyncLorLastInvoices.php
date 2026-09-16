<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Services\OdooService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncLorLastInvoices extends Command
{
    protected $signature = 'lor:sync-last-invoices {--chunk=200 : Chunk size for Odoo query}';
    protected $description = 'Fetch and update last invoice dates from Odoo for all rental items in the database';

    public function handle(OdooService $odoo): int
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(1200);
        DB::disableQueryLog();

        $this->info('Starting Last Invoice Date synchronization from Odoo...');

        // Query all distinct rental IDs
        $rentalIds = Item::withoutGlobalScopes()
            ->whereNotNull('rental_id')
            ->where('rental_id', '!=', '')
            ->distinct()
            ->pluck('rental_id')
            ->toArray();

        $total = count($rentalIds);
        $this->info("Found {$total} distinct rental IDs to check.");

        if ($total === 0) {
            $this->warn('No rental IDs found.');
            return 0;
        }

        $chunkSize = (int) $this->option('chunk');
        $chunks = array_chunk($rentalIds, $chunkSize);
        $bar = $this->output->createProgressBar(count($chunks));
        $bar->start();

        $totalUpdated = 0;

        foreach ($chunks as $chunk) {
            try {
                $datesMap = $odoo->fetchLastInvoiceDatesForRentalOrders($chunk);

                if (!empty($datesMap)) {
                    foreach ($datesMap as $rid => $date) {
                        Item::withoutGlobalScopes()
                            ->where('rental_id', $rid)
                            ->update(['last_invoice_date' => $date]);
                        $totalUpdated++;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Error syncing chunk of last invoice dates: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✓ Successfully updated {$totalUpdated} rental records with Last Invoice Dates.");

        return 0;
    }
}
