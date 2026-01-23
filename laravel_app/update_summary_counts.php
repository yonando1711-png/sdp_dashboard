<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\Storage;

// Standalone script, so no Facades.
// Just use direct file manipulation.

$itemsPath = 'storage/app/private/items.json';
$summaryPath = 'storage/app/private/summary.json';

if (!file_exists($itemsPath) || !file_exists($summaryPath)) {
    echo "Files not found.\n";
    exit(1);
}

$items = json_decode(file_get_contents($itemsPath), true);
$summary = json_decode(file_get_contents($summaryPath), true);

// Reset counts
$summary['rental_type_summary']['Subscription'] = 0;
$summary['rental_type_summary']['Regular'] = 0;

foreach ($items as $item) {
    if (!empty($item['is_sold'])) continue;
    
    $type = $item['rental_type'] ?? '';

    if ($type === 'Subscription') {
        $summary['rental_type_summary']['Subscription'] += $item['on_hand_quantity'];
    } elseif ($type === 'Regular') {
        $summary['rental_type_summary']['Regular'] += $item['on_hand_quantity'];
    }
}

file_put_contents($summaryPath, json_encode($summary, JSON_PRETTY_PRINT));

echo "Updated Summary:\n";
echo "Subscription: " . $summary['rental_type_summary']['Subscription'] . "\n";
echo "Regular: " . $summary['rental_type_summary']['Regular'] . "\n";
