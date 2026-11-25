<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSchedule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled'       => 'boolean',
        'prune_enabled' => 'boolean',
        'keep_daily'    => 'integer',
        'keep_weekly'   => 'integer',
        'keep_monthly'  => 'integer',
        'day_of_week'   => 'integer',
        'day_of_month'  => 'integer',
    ];
}
