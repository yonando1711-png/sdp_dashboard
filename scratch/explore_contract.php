<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OdooService;

$odoo = new OdooService();
$rentalId = 'R/2025/04078';

echo "Searching for Rental Order: $rentalId\n";

try {
    $soIds = $odoo->execute('sale.order', 'search', [[['name', '=', $rentalId]]]);
    
    if (empty($soIds)) {
        die("Rental Order $rentalId not found.\n");
    }
    
    $soId = $soIds[0];
    echo "Found SO ID: $soId\n";
    
    $so = $odoo->execute('sale.order', 'read', [[$soId]])[0];
    
    echo "Looking for 'Contract' fields in sale.order:\n";
    foreach ($so as $key => $value) {
        $strVal = is_array($value) && isset($value[1]) ? $value[1] : (is_string($value) ? $value : '');
        if (str_contains(strtolower($key), 'contract') || str_contains(strtolower($strVal), 'c/20') || str_contains(strtolower($strVal), 'acc/sdp')) {
            echo " - $key : " . print_r($value, true) . "\n";
        }
    }
    
    $fields = $odoo->detectFields('sale.order')['fields'] ?? [];
    foreach ($fields as $key => $field) {
        if (str_contains(strtolower($field['string'] ?? ''), 'contract') || str_contains(strtolower($key), 'contract')) {
            echo " - Field Metadata: $key -> " . ($field['string'] ?? '') . " (Relation: " . ($field['relation'] ?? 'none') . ")\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
