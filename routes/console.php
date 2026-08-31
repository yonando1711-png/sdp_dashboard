<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Odoo Auto-Sync Schedule
// Wrapped in try-catch to prevent errors during Docker build (when DB doesn't exist)
try {
    $scheduleEnabled = Setting::getValue('odoo_schedule_enabled', 'false') === 'true';
    $scheduleInterval = Setting::getValue('odoo_schedule_interval', 'daily');

    if ($scheduleEnabled) {
        $command = Schedule::command('odoo:sync');
        
        match ($scheduleInterval) {
            'hourly' => $command->hourly(),
            'every_2_hours' => $command->everyTwoHours(),
            'every_4_hours' => $command->everyFourHours(),
            'every_6_hours' => $command->everySixHours(),
            'every_12_hours' => $command->twiceDaily(0, 12),
            'daily' => $command->daily(),
            default => $command->daily(),
        };
    }

    // Auto Surat Kuasa Generation Schedule (separate from odoo:sync)
    $skAutoEnabled  = Setting::getValue('surat_kuasa_auto_enabled', 'false') === 'true';
    $skAutoInterval = Setting::getValue('surat_kuasa_auto_interval', 'hourly');

    if ($skAutoEnabled) {
        $skCommand = Schedule::command('sk:auto-generate');
        match ($skAutoInterval) {
            'every_30_min'  => $skCommand->everyThirtyMinutes(),
            'hourly'        => $skCommand->hourly(),
            'every_2_hours' => $skCommand->everyTwoHours(),
            'every_4_hours' => $skCommand->everyFourHours(),
            'every_6_hours' => $skCommand->everySixHours(),
            'daily'         => $skCommand->daily(),
            default         => $skCommand->hourly(),
        };
    }
} catch (\Exception $e) {
    // Database not available (e.g., during Docker build) - skip scheduling
}
