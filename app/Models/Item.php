<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasBranchScope;

class Item extends Model
{
    use HasBranchScope;
    protected $guarded = ['id'];

    protected $casts = [
        'is_order_only' => 'boolean',
        'on_hand_quantity' => 'float',
        'is_vendor_rent' => 'boolean',
        'is_on_hand' => 'boolean',
        'is_stock' => 'boolean',
        'in_stock' => 'boolean',
        'is_sold' => 'boolean',
        'is_active_rental' => 'boolean',
        'surat_kuasa_tracked' => 'boolean',
        'odoo_lot_id' => 'integer',
        'actual_start_rental' => 'date',
        'actual_end_rental' => 'date',
        'category_flags' => 'array',
        'km_last' => 'float',
        'rental_id_count' => 'integer',
        'product_movement_count' => 'integer',
        'repair_schedule_date' => 'date',
        'repair_estimation_end' => 'date',
        'repair_odometer' => 'integer',
        'purchase_date' => 'date',
        'last_invoice_date' => 'date',
        'first_start_sewa_date' => 'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope('exclude_order_only', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('items.is_order_only', false);
        });
    }

    // Scopes for common queries
    public function scopeActiveRental($query)
    {
        return $query->where('is_active_rental', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeVendorRent($query)
    {
        return $query->where('is_vendor_rent', true);
    }

    public function scopeSold($query)
    {
        return $query->where('is_sold', true);
    }

    /**
     * Disposal Module Accessors & Helpers
     */
    public function getDisposalDueDateAttribute(): ?\Carbon\Carbon
    {
        return $this->first_start_sewa_date ? $this->first_start_sewa_date->copy()->addYears(5) : null;
    }

    public function getServiceAgeStringAttribute(): string
    {
        if (!$this->first_start_sewa_date) {
            return '-';
        }

        $diff = $this->first_start_sewa_date->diff(now());
        return "{$diff->y} Thn {$diff->m} Bln";
    }

    public function getDisposalStatusAttribute(): string
    {
        $locUpper = strtoupper($this->location ?? '');
        if ($this->is_sold || str_contains($locUpper, 'SOLD') || str_contains($locUpper, 'DISPOSAL')) {
            return 'disposed';
        }

        if (!$this->first_start_sewa_date) {
            return 'never_rented';
        }

        $dueDate = $this->disposal_due_date;
        $now = now();

        if ($now->greaterThanOrEqualTo($dueDate)) {
            return 'due';
        }

        if ($now->greaterThanOrEqualTo($dueDate->copy()->subMonths(6))) {
            return 'approaching';
        }

        return 'active';
    }

    public function getFirstSentAsBadgeAttribute(): array
    {
        return match ($this->first_sent_as) {
            'ORIGINAL' => [
                'label' => 'ORIGINAL',
                'class' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20'
            ],
            'RBO' => [
                'label' => 'RBO',
                'class' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20'
            ],
            default => [
                'label' => '-',
                'class' => 'text-slate-500'
            ],
        };
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
