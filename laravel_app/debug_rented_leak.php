<?php
require 'vendor/autoload.php';

$itemsPath = 'storage/app/private/items.json';
$items = json_decode(file_get_contents($itemsPath), true);

$inCustomer = 0;
$caught = 0;
$uncaught = [];
$counts = [
    'Vendor Rent' => 0,
    'Original in Customer' => 0,
    'Replacement' => 0,
    'Check Rent position' => 0
];

foreach ($items as $item) {
    if (!empty($item['is_sold'])) continue;
    if ($item['location'] !== 'Partners/Customers/Rental') continue;

    $inCustomer++;
    
    $isVendor = $item['is_vendor_rent'];
    $rentalId = $item['rental_id'];
    $lotNo = $item['lot_number'];
    $reservedLot = $item['reserved_lot'];

    $matched = false;

    if ($isVendor) {
        $matched = true;
    } elseif ($lotNo == $reservedLot && !empty($reservedLot)) {
        $matched = true;
    } elseif (!empty($rentalId) && $lotNo != $reservedLot && !$isVendor) {
        $matched = true;
    } elseif (empty($rentalId)) {
        $matched = true;
    }

    if ($matched) {
        $caught++;
    } else {
        $uncaught[] = $item;
    }
    
    // SummaryGenerator Logic Replication
    if ($isVendor) {
        $counts['Vendor Rent']++;
    } elseif ($lotNo == $reservedLot && !empty($reservedLot)) {
        $counts['Original in Customer']++;
    } elseif (!empty($rentalId) && $lotNo != $reservedLot && !$isVendor) {
        // Replacement
        // Need to simulate rentalIdCounts logic if we want split, but total replacement is enough
        $counts['Replacement']++;
    } elseif (empty($rentalId)) {
        $counts['Check Rent position']++;
    }
}

echo "Total In Customer: $inCustomer\n";
echo "Caught: $caught\n";
echo "Uncaught (Leak): " . count($uncaught) . "\n\n";

echo "Breakdown:\n";
foreach ($counts as $k => $v) {
    echo "  - $k: $v\n";
}
echo "Sum of Breakdown: " . array_sum($counts) . "\n";

if (count($uncaught) > 0) {
    echo "First 5 Leaked Items:\n";
    foreach (array_slice($uncaught, 0, 5) as $leak) {
        print_r($leak);
    }
}
