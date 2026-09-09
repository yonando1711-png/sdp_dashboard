<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Services\OdooService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncDisposalMovements extends Command
{
    protected $signature = 'disposal:sync 
                            {--lot= : Specific lot number to sync} 
                            {--force : Overwrite existing first_start_sewa_date}';

    protected $description = 'Sync first rental dispatch movement, start sewa date, customer, and Sent As (ORIGINAL/RBO) from Odoo for the Disposal module';

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(600);
        DB::disableQueryLog();

        $odoo = app(OdooService::class);
        $specificLot = $this->option('lot');
        $force = (bool) $this->option('force');

        $this->info('==================================================');
        $this->info('  Disposal Fleet Lifecycle Movement Synchronization');
        $this->info('==================================================');

        if ($specificLot) {
            $this->line("Targeting single lot: <fg=yellow>{$specificLot}</>");
            $lotNumbers = [$specificLot];
        } else {
            $query = Item::query()
                ->whereNotNull('lot_number')
                ->where('lot_number', '!=', '');

            if (!$force) {
                $query->whereNull('first_sent_as');
            }

            $lotNumbers = $query->pluck('lot_number')->unique()->values()->toArray();
            $this->line('Found <fg=yellow>' . count($lotNumbers) . '</> fleet vehicles to evaluate' . ($force ? ' (forced mode)' : '') . '.');
        }

        if (empty($lotNumbers)) {
            $this->info('No vehicles to synchronize. All records are already up to date.');
            return Command::SUCCESS;
        }

        $chunks = array_chunk($lotNumbers, 50);
        $totalChunks = count($chunks);
        $totalUpdated = 0;

        $bar = $this->output->createProgressBar(count($lotNumbers));
        $bar->start();

        foreach ($chunks as $idx => $chunk) {
            try {
                $results = $odoo->fetchFirstRentalMovements($chunk);

                foreach ($chunk as $lot) {
                    if (isset($results[$lot])) {
                        $data = $results[$lot];
                        Item::where('lot_number', $lot)->update([
                            'first_rental_id' => $data['rental_id'],
                            'first_start_sewa_date' => $data['date'],
                            'first_customer_name' => $data['customer'],
                            'first_sent_as' => $data['sent_as'],
                        ]);
                        $totalUpdated++;
                    } else {
                        Item::where('lot_number', $lot)->update([
                            'first_sent_as' => 'NONE',
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Disposal sync batch error: ' . $e->getMessage(), ['chunk_index' => $idx]);
                $this->newLine();
                $this->error("Batch " . ($idx + 1) . " encountered an error: " . $e->getMessage());
            }

            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Synchronization finished successfully!");
        $this->line("Total items updated with disposal lifecycle data: <fg=green>{$totalUpdated}</>");

        return Command::SUCCESS;
    }
}
