<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratKuasaLog extends Model
{
    use HasFactory;

    protected $table = 'surat_kuasa_logs';

    protected $fillable = [
        'item_id',
        'doc_no',
        'lot_number',
        'product',
        'customer',
        'penerima_nama',
        'penerima_alamat',
        'jenis_model',
        'warna',
        'tahun',
        'no_rangka',
        'no_mesin',
        'print_date',
        'action_type',
        'recipient_email',
        'generated_by_id',
        'generated_by_name',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }
}
