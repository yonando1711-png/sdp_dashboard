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
     * Create an atomic database backup snapshot (supports both SQLite and MySQL)
     * and compress it with gzip (.sqlite.gz or .sql.gz).
     *
     * @param string|null $remark
     * @return string Filename of the generated backup
     * @throws \Exception
     */
    public function create(?string $remark = null): string
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(600);
        ignore_user_abort(true);

        $connectionName = config('database.default', 'sqlite');
        $config = config("database.connections.{$connectionName}");
        $driver = $config['driver'] ?? 'sqlite';

        if (!in_array($driver, ['sqlite', 'mysql'])) {
            throw new \Exception("Database driver '{$driver}' is not supported for backup. Expected 'sqlite' or 'mysql'.");
        }

        $timestamp = Carbon::now()->format('Y-m-d-H-i-s');
        $backupDir = Storage::disk($this->disk)->path($this->backupFolder);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        if ($driver === 'sqlite') {
            return $this->createSqliteBackup($connectionName, $config, $backupDir, $timestamp, $remark);
        } else {
            return $this->createMysqlBackup($connectionName, $config, $backupDir, $timestamp, $remark);
        }
    }

    /**
     * Create SQLite backup using VACUUM INTO + Gzip (.sqlite.gz).
     */
    protected function createSqliteBackup(string $connectionName, array $config, string $backupDir, string $timestamp, ?string $remark): string
    {
        $dbPath = $config['database'] ?? '';
        if ($dbPath === ':memory:' || !is_file($dbPath)) {
            throw new \Exception("SQLite database file not found at: {$dbPath}");
        }

        $dbSize = filesize($dbPath) ?: 0;
        $filename = "backup-{$timestamp}.sqlite.gz";

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
            $pdo = DB::connection($connectionName)->getPdo();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 10000');
            $pdo->exec('VACUUM INTO ' . $pdo->quote($tempSqlitePath));

            clearstatcache(true, $tempSqlitePath);
            if (!file_exists($tempSqlitePath) || filesize($tempSqlitePath) === 0) {
                throw new \Exception('Failed to generate SQLite snapshot: file is missing or empty.');
            }

            $fp = fopen($tempSqlitePath, 'rb');
            if (!$fp) {
                throw new \Exception('Failed to open temporary snapshot for reading.');
            }

            // Using wb4 for fast compression (avoids timeout)
            $gz = gzopen($targetGzPath, 'wb4');
            if (!$gz) {
                fclose($fp);
                throw new \Exception("Cannot open gzip file for writing at {$targetGzPath}");
            }

            while (!feof($fp)) {
                gzwrite($gz, fread($fp, 262144));
            }

            fclose($fp);
            gzclose($gz);
            @unlink($tempSqlitePath);

            clearstatcache(true, $targetGzPath);
            if (!file_exists($targetGzPath) || filesize($targetGzPath) === 0) {
                throw new \Exception('Gzip compression failed: output file is missing or empty.');
            }

            return $this->recordBackupLog($filename, $targetGzPath, $remark);
        } catch (\Throwable $e) {
            if (file_exists($tempSqlitePath)) {
                @unlink($tempSqlitePath);
            }
            if (file_exists($targetGzPath)) {
                @unlink($targetGzPath);
            }
            Log::error('BackupService (SQLite) failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create MySQL backup using streaming SQL export + Gzip (.sql.gz).
     */
    protected function createMysqlBackup(string $connectionName, array $config, string $backupDir, string $timestamp, ?string $remark): string
    {
        $filename = "backup-{$timestamp}.sql.gz";
        $targetGzPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        // Try mysqldump if available, otherwise use native PDO streaming
        $dumpSuccess = $this->tryMysqldump($config, $targetGzPath);

        if (!$dumpSuccess) {
            $this->createMysqlPdoDump($connectionName, $config, $targetGzPath);
        }

        clearstatcache(true, $targetGzPath);
        if (!file_exists($targetGzPath) || filesize($targetGzPath) === 0) {
            throw new \Exception('MySQL backup failed: output file is missing or empty.');
        }

        return $this->recordBackupLog($filename, $targetGzPath, $remark);
    }

    /**
     * Attempt mysqldump command line export.
     */
    protected function tryMysqldump(array $config, string $targetGzPath): bool
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        if (empty($database) || empty($username)) {
            return false;
        }

        // Test if mysqldump is installed
        $binary = 'mysqldump';
        $testCmd = (DIRECTORY_SEPARATOR === '\\') ? 'where mysqldump 2>NUL' : 'which mysqldump 2>/dev/null';
        @exec($testCmd, $output, $returnVar);
        if ($returnVar !== 0) {
            if (DIRECTORY_SEPARATOR === '\\' && file_exists('D:\\xampp\\mysql\\bin\\mysqldump.exe')) {
                $binary = 'D:\\xampp\\mysql\\bin\\mysqldump.exe';
            } elseif (DIRECTORY_SEPARATOR === '\\' && file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
                $binary = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
            } else {
                return false;
            }
        }

        $pwdFlag = empty($password) ? '' : '-p' . escapeshellarg($password);
        $tempSqlPath = dirname($targetGzPath) . DIRECTORY_SEPARATOR . 'temp_' . uniqid() . '.sql';

        $dumpCmd = sprintf(
            '%s --single-transaction --quick --skip-lock-tables -h %s -P %s -u %s %s %s > %s 2>&1',
            escapeshellarg($binary),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $pwdFlag,
            escapeshellarg($database),
            escapeshellarg($tempSqlPath)
        );

        @exec($dumpCmd, $cmdOutput, $exitCode);

        if ($exitCode === 0 && file_exists($tempSqlPath) && filesize($tempSqlPath) > 0) {
            // Compress with gzip wb4
            $fp = fopen($tempSqlPath, 'rb');
            $gz = gzopen($targetGzPath, 'wb4');
            while (!feof($fp)) {
                gzwrite($gz, fread($fp, 262144));
            }
            fclose($fp);
            gzclose($gz);
            @unlink($tempSqlPath);
            return true;
        }

        if (file_exists($tempSqlPath)) {
            @unlink($tempSqlPath);
        }

        return false;
    }

    /**
     * Native PHP PDO streaming dump for MySQL. Zero external binaries required.
     */
    protected function createMysqlPdoDump(string $connectionName, array $config, string $targetGzPath): void
    {
        try {
            $pdo = DB::connection($connectionName)->getPdo();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

            $gz = gzopen($targetGzPath, 'wb4');
            if (!$gz) {
                throw new \Exception("Cannot open gzip file for writing at {$targetGzPath}");
            }

            $dbName = $config['database'] ?? 'sdp';

            // Initial SQL dump header
            gzwrite($gz, "-- SDP Dashboard MySQL Backup\n");
            gzwrite($gz, "-- Database: `{$dbName}`\n");
            gzwrite($gz, "-- Generated: " . Carbon::now()->toDateTimeString() . "\n\n");
            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=0;\n");
            gzwrite($gz, "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n");
            gzwrite($gz, "SET NAMES utf8mb4;\n\n");

            // Fetch list of tables
            $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // Table schema
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(PDO::FETCH_NUM);
                $createSql = $createRow[1] ?? '';

                gzwrite($gz, "-- Table structure for table `{$table}`\n");
                gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n");
                gzwrite($gz, $createSql . ";\n\n");

                // Table rows dump (chunked streaming to save memory)
                gzwrite($gz, "-- Dumping data for table `{$table}`\n");
                $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
                
                $batch = [];
                $batchSize = 200;

                while ($row = $rowsStmt->fetch(PDO::FETCH_NUM)) {
                    $escapedValues = array_map(function ($val) use ($pdo) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        return $pdo->quote($val);
                    }, $row);

                    $batch[] = '(' . implode(', ', $escapedValues) . ')';

                    if (count($batch) >= $batchSize) {
                        gzwrite($gz, "INSERT INTO `{$table}` VALUES \n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    gzwrite($gz, "INSERT INTO `{$table}` VALUES \n" . implode(",\n", $batch) . ";\n");
                }

                gzwrite($gz, "\n");
            }

            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
            gzclose($gz);
        } catch (\Throwable $e) {
            if (file_exists($targetGzPath)) {
                @unlink($targetGzPath);
            }
            Log::error('BackupService (MySQL PDO Dump) failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Record generated backup file into database log.
     */
    protected function recordBackupLog(string $filename, string $targetGzPath, ?string $remark): string
    {
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

        Log::info("Database backup successfully created: {$filename} ({$finalSize} bytes)");

        return $filename;
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
