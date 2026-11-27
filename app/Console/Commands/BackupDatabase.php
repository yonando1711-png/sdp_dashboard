<?php

namespace App\Console\Commands;

use App\Models\BackupSchedule;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-db {--remark=Scheduled Backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a compressed database backup snapshot (.sql.gz / .sqlite.gz)';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $conn = config('database.default', 'mysql');
        $this->info("Starting {$conn} database backup snapshot...");

        try {
            $filename = $backupService->create($this->option('remark'));
            $this->info("Backup created successfully: {$filename}");
            Log::info("Backup created successfully via CLI: {$filename}");

            // Auto prune old backups if enabled in schedule settings
            $schedule = BackupSchedule::first();
            if ($schedule && $schedule->prune_enabled) {
                $this->info('Pruning old backups according to retention policy...');
                $result = $backupService->prune(
                    $schedule->keep_daily ?? 7,
                    $schedule->keep_weekly ?? 4,
                    $schedule->keep_monthly ?? 6
                );
                $this->info("Pruning complete: kept {$result['kept']} backups, deleted {$result['deleted']}.");
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            Log::error('BackupDatabase command failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
