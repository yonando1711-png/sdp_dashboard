<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OdooService;

$odoo = new OdooService();

$fields = $odoo->detectFields('sale.order')['fields'] ?? [];
echo "Metadata for rental_contract_id:\n";
print_r($fields['rental_contract_id'] ?? 'Not found');

$contractId = 14;
// Try to guess the model name. 
// "rental_contract_id" -> model might be "rental.contract" or "sale.contract"
$models = ['rental.contract', 'fleet.vehicle.log.contract'];
foreach ($models as $m) {
    try {
        $c = $odoo->execute($m, 'read', [[$contractId]]);
        if (!empty($c)) {
            echo "\nFound in model $m:\n";
            foreach ($c[0] as $k => $v) {
                $strVal = is_array($v) && isset($v[1]) ? $v[1] : (is_string($v) ? $v : '');
                if (str_contains(strtolower($k), 'ref') || str_contains(strtolower($strVal), '1704')) {
                    echo " - $k : " . print_r($v, true) . "\n";
                }
            }
            break;
        }
    } catch (\Exception $e) {
        // ignore
    }
}
