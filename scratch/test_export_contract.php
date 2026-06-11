<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OdooService;

$odoo = new OdooService();

$exportFields = [
    'name',
    'rental_id/display_name',
    'rental_id/rental_contract_id/display_name',
    'rental_id/rental_contract_id/reference'
];

$ids = $odoo->execute('stock.lot', 'search', [[['name', '=', 'RBB9531TD']]], ['limit' => 1]); // The lot in the first screenshot R/2025/04078 -> RBB9531TD? Let's search by rental_id R/2025/04078

$lotIds = $odoo->execute('stock.lot', 'search', [[['rental_id.name', '=', 'R/2025/04078']]]);

if (!empty($lotIds)) {
    echo "Lot IDs found: " . implode(', ', $lotIds) . "\n";
    $result = $odoo->execute('stock.lot', 'export_data', [[$lotIds[0]], $exportFields]);
    print_r($result);
} else {
    echo "No lot found for that rental order.\n";
}
