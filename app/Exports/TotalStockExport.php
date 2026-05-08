<?php

namespace App\Exports;

use App\Models\Item;
use App\Services\InventoryService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TotalStockExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $filters;
    protected $sortCol;
    protected $sortAsc;

    public function __construct(array $filters = [], string $sortCol = 'id', bool $sortAsc = true)
    {
        $this->filters = $filters;
        $this->sortCol = $sortCol;
        $this->sortAsc = $sortAsc;
    }

    public function query()
    {
        $query = Item::query()->where('is_sold', false)->where('on_hand_quantity', '>', 0);
        
        $inventory = app(InventoryService::class);
        
        if (!empty($this->filters)) {
            $inventory->applyAdvancedFilters($query, $this->filters);
        }

        $direction = $this->sortAsc ? 'asc' : 'desc';
        if ($this->sortCol === 'status') {
            $query->orderBy('rental_id', $direction);
        } else {
            $query->orderBy($this->sortCol, $direction);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Lot Number',
            'Internal Reference',
            'Product',
            'Location',
            'Qty',
            'Rental Status',
            'Rental Type',
            'Ownership',
            'Customer',
            'Warehouse',
            'Start Date',
            'End Date',
            'Role',
            'Linked Vehicle',
            'In Stock',
            'Contract',
        ];
    }

    public function map($item): array
    {
        $status = '-';
        if ($item->rental_id) {
            $status = $item->rental_id;
        } elseif ($item->in_stock) {
            $status = 'In Stock';
        }

        return [
            $item->lot_number,
            $item->internal_reference ?: 'No Ref',
            $item->product,
            $item->location,
            $item->on_hand_quantity,
            $status,
            $item->rental_type,
            $item->is_vendor_rent ? 'Vendor' : 'Owned',
            $item->current_customer ?: '-',
            $item->warehouse ?: '-',
            $item->actual_start_rental ? $item->actual_start_rental->format('Y-m-d') : '',
            $item->actual_end_rental ? $item->actual_end_rental->format('Y-m-d') : '',
            $item->vehicle_role,
            $item->linked_vehicle,
            $item->in_stock ? 'Yes' : 'No',
            $item->contract_ref ?: '-',
        ];
    }
}
