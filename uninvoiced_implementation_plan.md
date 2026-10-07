# Implementation Plan: Uninvoiced Accounting Report Refactoring

> [!NOTE]
> **Strict Isolation Guarantee**: This plan modifies **ONLY** the Uninvoiced Accounting module ([app/Services/OdooService.php](file:///d:/project_sdp/sdp_dashboard-main/app/Services/OdooService.php) line 4142 onwards). No other dashboards, operational modules, disposal, billing, or fleet systems will be touched or affected in any way.

---

## 1. Objective & Scope

Align the Uninvoiced Accounting report in `sdp_dashboard` to match the legacy FlexCel report (`flexcel.xls` / `flexcel di proses.xls`):
1. **Accrual Logic (Time Machine)**: Only count rental earned up to the Cutoff Date that was unbilled as of that date.
2. **One Row per Unit**: Group billing periods by Vehicle/Unit instead of showing multiple rows per month.
3. **Prorate Duration (`nlen`)**: Calculate exact month fractions (`full months + days / 30`).
4. **Correct Pricing**: Calculate unit accrued value (`monthly price incl. PPN × nlen`), fixing the 162 Billion bug caused by using invoice totals.
5. **Filter Cancelled Contracts**: Automatically exclude replaced/cancelled Sale Orders (`state === 'cancel'`).
6. **Track Realization Invoices**: List invoices issued post-cutoff with their dates (matching `elist`).

---

## 2. Detailed Steps

### Step 1: Pre-Change Safety Backup
- Execute local Git commit to create a rollback checkpoint before touching any code.

### Step 2: Algorithmic Core in [OdooService.php](file:///d:/project_sdp/sdp_dashboard-main/app/Services/OdooService.php)
1. **Add `calculateNlen(string $startDate, string $endDate): float`**:
   - Implements the legacy formula verified against 1,305 rows:
     - Full calendar month = `1.00`
     - Partial month = `days / 30.0`
     - Handles 31st day start (e.g. 31 Jan → 30 Apr = `3.00`).
2. **Update `fetchUninvoicedAccountingMaster()`**:
   - Add `'state'` to the fields fetched for `sale.order`.
3. **Refactor `compileUninvoicedReport()`**:
   - Resolve missing SO states on the fly for cached data.
   - Filter out cancelled SOs (`$so['state'] === 'cancel'`).
   - Group records by Unit: `key = "{$soId}_{$lotId}"`.
   - For each unit:
     - Collect unbilled periods whose `start_rental_period_date <= $cutoffDate`.
     - Skip units with 0 unbilled periods (fully billed).
     - Calculate `ddtstr = min(start_rental_period_date)`.
     - Calculate `ddtend = min(max(end_rental_period_date), cutoffDate, actualEndRental)`.
     - Compute `nlen = calculateNlen($ddtstr, $ddtend)`.
     - Compute `hg_sw = round(durationPrice * 1.11)` and `jurnal_accrued = round(hg_sw * nlen)`.
     - Collect post-cutoff realization invoices (`INVRS/YYYY/XXXXX dd/mm/yyyy`).
     - Map unit status (`post_cutoff`, `draft`, `reversed`, `uninvoiced`).
     - Populate row with backward-compatible keys (`total` = `jurnal_accrued`, `duration` = `nlen`).
     - Distribute monthly accrued value into Customer Pivot table.

### Step 3: Verification & Validation
1. **Verify the 6 Highlighted Vehicles**:
   - `DD-1449-XDQ` (ANTAM) → 01/03 to 30/04, `nlen = 2.00`, Accrued = `17,926,500`
   - `DD-1449-XDO` (ANTAM) → 01/03 to 30/04, `nlen = 2.00`, Accrued = `17,926,500`
   - `DD-1450-XDL` (ANTAM) → 01/03 to 30/04, `nlen = 2.00`, Accrued = `14,552,100`
   - `DD-1518-XDQ` (ANTAM) → 01/03 to 30/04, `nlen = 2.00`, Accrued = `20,035,500`
   - `B -9441-BXE` (AMARAMAPRI) → 01/04 to 30/04, `nlen = 1.00`, Accrued = `3,718,500`
   - `B -1655-HZC` (AIRAJIKASA) → 09/04 to 30/04, `nlen = 0.73`, Accrued = `3,079,140`
2. **Verify Total Portfolio Value**:
   - Verify overall accrued total lands around ~10.45 Bn (matching legacy 10.53 Bn), completely fixing the 162 Bn bug.

### Step 4: UI & Export Check
1. Verify web view at [resources/views/accounting/uninvoiced.blade.php](file:///d:/project_sdp/sdp_dashboard-main/resources/views/accounting/uninvoiced.blade.php) renders cleanly.
2. Verify Excel exports ([UninvoicedAccountingDetailedExport.php](file:///d:/project_sdp/sdp_dashboard-main/app/Exports/UninvoicedAccountingDetailedExport.php)) generate the exact expected figures.

---

## 3. Rollback Plan
If any discrepancy occurs, `git checkout` will instantly restore the file to the exact safe commit.
