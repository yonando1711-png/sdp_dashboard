<?php

namespace App\Services;

use App\Models\BackupLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDO;

class BackupService
{
    protected string $disk = 'local';
    protected string $backupFolder = 'backups';

    /**
     * Create an atomic SQLite snapshot backup and compress it with gzip (.sqlite.gz).
     *
     * @param string|null $remark
     * @return string Filename of the generated backup
     * @throws \Exception
     */
    public function create(?string $remark = null): string
    {
        $connectionName = config('database.default', 'sqlite');
        $config = config("database.connections.{$connectionName}");

        if (($config['driver'] ?? null) !== 'sqlite') {
            throw new \Exception("Default database driver is '{$config['driver']}', expected 'sqlite'.");
        }

        $dbPath = $config['database'] ?? '';
        if ($dbPath === ':memory:' || !is_file($dbPath)) {
            throw new \Exception("SQLite database file not found at: {$dbPath}");
        }

        $dbSize = filesize($dbPath) ?: 0;

        $timestamp = Carbon::now()->format('Y-m-d-H-i-s');
        $filename = "backup-{$timestamp}.sqlite.gz";

        $backupDir = Storage::disk($this->disk)->path($this->backupFolder);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Check disk space (require at least 1.5x DB size)
        $freeBytes = @disk_free_space($backupDir);
        if ($freeBytes !== false && $freeBytes < ($dbSize * 1.5)) {
            throw new \Exception(sprintf(
                'Insufficient disk space: need ~%.1f MB, available only %.1f MB.',
                ($dbSize * 1.5) / 1048576,
                $freeBytes / 1048576
            ));
        }

        $tempSqlitePath = $backupDir . DIRECTORY_SEPARATOR . 'temp_' . uniqid() . '.sqlite';
        $targetGzPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        try {
            // 1. Atomic online SQLite snapshot via VACUUM INTO
            $pdo = DB::connection($connectionName)->getPdo();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 10000');
            $pdo->exec('VACUUM INTO ' . $pdo->quote($tempSqlitePath));

            clearstatcache(true, $tempSqlitePath);
            if (!file_exists($tempSqlitePath) || filesize($tempSqlitePath) === 0) {
                throw new \Exception('Failed to generate SQLite snapshot: file is missing or empty.');
            }

            // 2. Compress snapshot into .sqlite.gz
            $fp = fopen($tempSqlitePath, 'rb');
            if (!$fp) {
                throw new \Exception('Failed to open temporary snapshot for reading.');
            }

            $gz = gzopen($targetGzPath, 'wb9');
            if (!$gz) {
                fclose($fp);
                throw new \Exception("Cannot open gzip file for writing at {$targetGzPath}");
            }

            while (!feof($fp)) {
                gzwrite($gz, fread($fp, 65536));
            }

            fclose($fp);
            gzclose($gz);

            // Clean up temporary uncompressed snapshot
            @unlink($tempSqlitePath);

            clearstatcache(true, $targetGzPath);
            if (!file_exists($targetGzPath) || filesize($targetGzPath) === 0) {
                throw new \Exception('Gzip compression failed: output file is missing or empty.');
            }

            $finalSize = filesize($targetGzPath);

            $creator = 'System/Scheduler';
            if (Auth::check() && Auth::user()) {
                $creator = Auth::user()->name ?? 'User #' . Auth::id();
            }

            BackupLog::create([
                'filename'   => $filename,
                'path'       => $this->backupFolder . '/' . $filename,
                'disk'       => $this->disk,
                'size'       => $finalSize,
                'remark'     => $remark,
                'created_by' => $creator,
            ]);

            Log::info("SQLite backup successfully created: {$filename} ({$finalSize} bytes)");

            return $filename;

        } catch (\Throwable $e) {
            if (file_exists($tempSqlitePath)) {
                @unlink($tempSqlitePath);
            }
            if (file_exists($targetGzPath)) {
                @unlink($targetGzPath);
            }
            Log::error('BackupService failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get list of backups ordered by latest.
     */
    public function list()
    {
        return BackupLog::orderByDesc('created_at')->get();
    }

    /**
     * Download a backup file.
     */
    public function download(string $filename)
    {
        $path = $this->backupFolder . '/' . $filename;
        if (!Storage::disk($this->disk)->exists($path)) {
            abort(404, 'File backup tidak ditemukan.');
        }

        return Storage::disk($this->disk)->download($path, $filename, [
            'Content-Type' => 'application/gzip',
        ]);
    }

    /**
     * Delete a backup file and its log.
     */
    public function delete(string $filename): bool
    {
        $path = $this->backupFolder . '/' . $filename;

        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }

        BackupLog::where('filename', $filename)->delete();
        return true;
    }

    /**
     * Prune old backups based on retention policy.
     */
    public function prune(int $keepDaily = 7, int $keepWeekly = 4, int $keepMonthly = 6): array
    {
        $backups = BackupLog::orderByDesc('created_at')->get();

        $keepSet = [];
        $dailyCounts = [];
        $weeklyCounts = [];
        $monthlyCounts = [];

        foreach ($backups as $backup) {
            $date = $backup->created_at;
            $dayKey = $date->format('Y-m-d');
            $weekKey = $date->format('Y-W');
            $monthKey = $date->format('Y-m');
            $keep = false;

            if (!isset($dailyCounts[$dayKey])) {
                $dailyCounts[$dayKey] = 0;
            }
            if ($dailyCounts[$dayKey] < 1 && count($dailyCounts) <= $keepDaily) {
                $keep = true;
                $dailyCounts[$dayKey]++;
            }

            if (!isset($weeklyCounts[$weekKey])) {
                $weeklyCounts[$weekKey] = 0;
            }
            if ($weeklyCounts[$weekKey] < 1 && count($weeklyCounts) <= $keepWeekly) {
                $keep = true;
                $weeklyCounts[$weekKey]++;
            }

            if (!isset($monthlyCounts[$monthKey])) {
                $monthlyCounts[$monthKey] = 0;
            }
            if ($monthlyCounts[$monthKey] < 1 && count($monthlyCounts) <= $keepMonthly) {
                $keep = true;
                $monthlyCounts[$monthKey]++;
            }

            if ($keep) {
                $keepSet[$backup->filename] = true;
            }
        }

        $deleted = [];
        foreach ($backups as $backup) {
            if (!isset($keepSet[$backup->filename])) {
                try {
                    $this->delete($backup->filename);
                    $deleted[] = $backup->filename;
                } catch (\Throwable $e) {
                    Log::error("Prune error on {$backup->filename}: " . $e->getMessage());
                }
            }
        }

        return ['kept' => count($keepSet), 'deleted' => count($deleted)];
    }
}
