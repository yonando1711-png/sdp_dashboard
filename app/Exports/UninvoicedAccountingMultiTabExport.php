<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Multi-Tab Excel Export for Uninvoiced Accounting:
 * Sheet 1: Customer Monthly Summary (Pivot) via UninvoicedAccountingCustomerExport
 * Sheet 2: Detailed Line-Item Audit (22 Columns) via UninvoicedAccountingDetailedExport
 */
class UninvoicedAccountingMultiTabExport implements WithMultipleSheets
{
    protected array $reportData;
    protected string $cutoffDate;
    protected string $startMonth;
    protected string $endMonth;
    protected string $statusFilter;

    public function __construct(array $reportData, string $cutoffDate, string $startMonth, string $endMonth, string $statusFilter = 'all')
    {
        $this->reportData = $reportData;
        $this->cutoffDate = $cutoffDate;
        $this->startMonth = $startMonth;
        $this->endMonth = $endMonth;
        $this->statusFilter = $statusFilter;
    }

    public function sheets(): array
    {
        return [
            new UninvoicedAccountingCustomerExport($this->reportData, $this->cutoffDate, $this->startMonth, $this->endMonth),
            new UninvoicedAccountingDetailedExport($this->reportData, $this->cutoffDate, $this->startMonth, $this->endMonth, $this->statusFilter),
        ];
    }
}
