<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Get human-readable file size format (e.g. 15.24 MB)
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size ?? 0;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
