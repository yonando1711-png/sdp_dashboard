<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LorSmdSoItem extends Model
{
    protected $table = 'lor_smd_so_items';

    protected $fillable = [
        'batch_id',
        'so_number',
        'rental_type',
        'transaction_type',
        'sales_team',
        'salesperson',
        'customer_code',
        'customer_name',
        'duration_months',
        'order_date',
        'start_date',
        'end_date',
        'product',
        'reserved_lot',
        'rbo',
        'previous_so',
        'area',
        'duration_price',
        'total_price',
        'first_invoice_date',
        'komisi',
        'opsi_beli',
        'tags',
        'source_doc',
        'actual_end_rental_date',
        'previous_end_date',
        'previous_duration_price',
        'previous_customer',
        'previous_end_date_cust',
        'is_printed',
        'raw_odoo_data',
    ];

    protected $casts = [
        'order_date'              => 'date',
        'start_date'              => 'date',
        'end_date'                => 'date',
        'first_invoice_date'      => 'date',
        'actual_end_rental_date'  => 'date',
        'previous_end_date'       => 'date',
        'previous_end_date_cust'  => 'date',
        'duration_price'          => 'decimal:2',
        'total_price'             => 'decimal:2',
        'komisi'                  => 'decimal:2',
        'previous_duration_price' => 'decimal:2',
        'is_printed'              => 'boolean',
        'raw_odoo_data'           => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LorSmdSoBatch::class, 'batch_id');
    }

    // Scopes
    public function scopeUnprinted($query)
    {
        return $query->where('is_printed', false);
    }

    public function scopePrinted($query)
    {
        return $query->where('is_printed', true);
    }

    public function scopeForTeam($query, ?string $team)
    {
        if (!empty($team) && $team !== 'all') {
            return $query->where('sales_team', $team);
        }
        return $query;
    }
}
