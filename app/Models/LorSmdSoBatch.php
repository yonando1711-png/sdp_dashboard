<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LorSmdSoBatch extends Model
{
    protected $table = 'lor_smd_so_batches';

    protected $fillable = [
        'batch_no',
        'sales_team',
        'printed_by_id',
        'printed_by_name',
        'printed_at',
        'total_so',
        'pdf_path',
        'notes',
        'print_count',
    ];

    protected $casts = [
        'printed_at'  => 'datetime',
        'total_so'    => 'integer',
        'print_count' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(LorSmdSoItem::class, 'batch_id');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by_id');
    }
}
