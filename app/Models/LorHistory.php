<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LorHistory extends Model
{
    protected $fillable = [
        'rental_id',
        'import_log_id',
        'contract_ref',
        'product',
        'lot_number',
        'year',
        'city',
        'current_customer',
        'po',
        'actual_start_rental',
        'actual_end_rental',
        'price',
        'status',
        'driver',
        'product_movement_count',
    ];

    protected $casts = [
        'actual_start_rental' => 'date',
        'actual_end_rental'   => 'date',
    ];
}
