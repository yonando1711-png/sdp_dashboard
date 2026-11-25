<?php

namespace App\Http\Controllers;

use App\Models\BackupSchedule;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Show backup manager interface (IT Admin only).
     */
    public function index()
    {
        $backups = $this->backupService->list();
        $schedule = BackupSchedule::first() ?? new BackupSchedule([
            'enabled'       => true,
            'frequency'     => 'daily',
            'time'          => '02:00',
            'day_of_week'   => 1,
            'day_of_month'  => 1,
            'prune_enabled' => true,
            'keep_daily'    => 7,
            'keep_weekly'   => 4,
            'keep_monthly'  => 6,
        ]);

        return view('settings.backups', compact('backups', 'schedule'));
    }

    /**
     * Trigger an instant manual backup.
     */
    public function create(Request $request)
    {
        $request->validate([
            'remark' => 'nullable|string|max:255',
        ]);

        try {
            $filename = $this->backupService->create($request->input('remark'));
            return redirect()->route('backups.index')->with('success', "Backup database berhasil dibuat: {$filename}");
        } catch (\Throwable $e) {
            Log::error('Backup creation failed: ' . $e->getMessage());
            return redirect()->route('backups.index')->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    /**
     * Update backup schedule configuration.
     */
    public function updateSchedule(Request $request)
    {
        $request->validate([
            'enabled'       => 'nullable|boolean',
            'frequency'     => 'required|in:daily,weekly,monthly',
            'time'          => 'required|date_format:H:i',
            'day_of_week'   => 'nullable|integer|between:0,6',
            'day_of_month'  => 'nullable|integer|between:1,31',
            'prune_enabled' => 'nullable|boolean',
            'keep_daily'    => 'nullable|integer|min:1|max:365',
            'keep_weekly'   => 'nullable|integer|min:1|max:52',
            'keep_monthly'  => 'nullable|integer|min:1|max:24',
        ]);

        BackupSchedule::updateOrCreate(
            ['id' => 1],
            [
                'enabled'       => $request->boolean('enabled'),
                'frequency'     => $request->input('frequency'),
                'time'          => $request->input('time'),
                'day_of_week'   => $request->input('day_of_week', 1),
                'day_of_month'  => $request->input('day_of_month', 1),
                'prune_enabled' => $request->boolean('prune_enabled'),
                'keep_daily'    => $request->input('keep_daily', 7),
                'keep_weekly'   => $request->input('keep_weekly', 4),
                'keep_monthly'  => $request->input('keep_monthly', 6),
            ]
        );

        return redirect()->route('backups.index')->with('success', 'Pengaturan jadwal backup berhasil disimpan!');
    }

    /**
     * Download backup file (.sqlite.gz).
     */
    public function download(string $filename)
    {
        return $this->backupService->download($filename);
    }

    /**
     * Delete a backup file.
     */
    public function delete(string $filename)
    {
        try {
            $this->backupService->delete($filename);
            return redirect()->route('backups.index')->with('success', "File backup {$filename} berhasil dihapus.");
        } catch (\Throwable $e) {
            return redirect()->route('backups.index')->with('error', 'Gagal menghapus file backup: ' . $e->getMessage());
        }
    }

    /**
     * Manually trigger retention pruning.
     */
    public function prune()
    {
        $schedule = BackupSchedule::first();

        try {
            $result = $this->backupService->prune(
                $schedule->keep_daily ?? 7,
                $schedule->keep_weekly ?? 4,
                $schedule->keep_monthly ?? 6
            );
            return redirect()->route('backups.index')
                ->with('success', "Pruning selesai: Menahan {$result['kept']} backup, menghapus {$result['deleted']} file backup usang.");
        } catch (\Throwable $e) {
            return redirect()->route('backups.index')->with('error', 'Gagal membersihkan backup usang: ' . $e->getMessage());
        }
    }
}
