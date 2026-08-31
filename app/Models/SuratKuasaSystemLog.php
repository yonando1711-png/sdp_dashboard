<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKuasaSystemLog extends Model
{
    use HasFactory;

    protected $table = 'surat_kuasa_system_logs';

    protected $fillable = [
        'level',
        'event_type',
        'lot_number',
        'doc_no',
        'message',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function log(string $level, string $eventType, string $message, ?array $details = null, ?string $lotNumber = null, ?string $docNo = null): self
    {
        return self::create([
            'level'      => $level,
            'event_type' => $eventType,
            'message'    => $message,
            'details'    => $details,
            'lot_number' => $lotNumber,
            'doc_no'     => $docNo,
        ]);
    }

    public static function info(string $message, string $eventType = 'system', ?array $details = null, ?string $lotNumber = null, ?string $docNo = null): self
    {
        return self::log('info', $eventType, $message, $details, $lotNumber, $docNo);
    }

    public static function success(string $message, string $eventType = 'system', ?array $details = null, ?string $lotNumber = null, ?string $docNo = null): self
    {
        return self::log('success', $eventType, $message, $details, $lotNumber, $docNo);
    }

    public static function warning(string $message, string $eventType = 'system', ?array $details = null, ?string $lotNumber = null, ?string $docNo = null): self
    {
        return self::log('warning', $eventType, $message, $details, $lotNumber, $docNo);
    }

    public static function error(string $message, string $eventType = 'system', ?array $details = null, ?string $lotNumber = null, ?string $docNo = null): self
    {
        return self::log('error', $eventType, $message, $details, $lotNumber, $docNo);
    }
}
