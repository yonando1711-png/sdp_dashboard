<?php
require 'vendor/autoload.php';

$itemsPath = 'storage/app/private/items.json';
$items = json_decode(file_get_contents($itemsPath), true);

// 1. Build Index and Counts
$rentalIdCounts = [];
$itemsByRentalId = [];

foreach ($items as $item) {
    if (!empty($item['rental_id'])) {
        $rid = $item['rental_id'];
        $rentalIdCounts[$rid] = ($rentalIdCounts[$rid] ?? 0) + 1;
        $itemsByRentalId[$rid][] = $item;
    }
}

// 2. Find the 40 "Replacement - Service" items
$replacementsInCustomer = [];

foreach ($items as $item) {
    if (!empty($item['is_sold'])) continue;
    if ($item['location'] !== 'Partners/Customers/Rental') continue;
    
    $rentalId = $item['rental_id'] ?? '';
    $lotNo = $item['lot_number'] ?? '';
    $reservedLot = $item['reserved_lot'] ?? '';
    $isVendor = $item['is_vendor_rent'];

    if (!empty($rentalId) && $lotNo != $reservedLot && !$isVendor) {
        $count = $rentalIdCounts[$rentalId] ?? 0;
        if ($count > 1) {
            $replacementsInCustomer[] = $item;
        }
    }
}

echo "Found " . count($replacementsInCustomer) . " Replacement-Service items in Customer Rental.\n\n";

// 3. Find their Originals
$stats = [
    'In External Service' => 0,
    'In Internal Service' => 0,
    'In Insurance' => 0,
    'In Stock (Active)' => 0,
    'In Stock (Sold/Reserve)' => 0,
    'Sold' => 0,
    'Unknown/Missing' => 0,
    'Other' => 0
];

$discrepancyList = [];

foreach ($replacementsInCustomer as $rep) {
    $rid = $rep['rental_id'];
    $reservedLot = $rep['reserved_lot'];
    
    // Find the original vehicle in the group
    $group = $itemsByRentalId[$rid] ?? [];
    $original = null;
    
    foreach ($group as $candidate) {
        if ($candidate['lot_number'] == $reservedLot) {
            $original = $candidate;
            break;
        }
    }
    
    if (!$original) {
        $stats['Unknown/Missing']++;
        $discrepancyList[] = "RID: $rid | Replacement: {$rep['lot_number']} | Original Missing ($reservedLot)";
        continue;
    }
    
    $loc = $original['location'];
    $isSold = !empty($original['is_sold']);
    
    if ($isSold) {
        $stats['Sold']++;
        $discrepancyList[] = "RID: $rid | Original SOLD ($reservedLot)";
    } elseif (stripos($loc, 'Partners/Vendors/Service') === 0) {
        $stats['In External Service']++;
    } elseif ($loc == 'Physical Locations/Service') {
        $stats['In Internal Service']++;
    } elseif (stripos($loc, 'Partners/Vendors/Insurance') === 0) {
        $stats['In Insurance']++;
    } elseif (!empty($original['in_stock'])) {
        $stats['In Stock (Active)']++;
        $discrepancyList[] = "RID: $rid | Original IN STOCK ($reservedLot) at $loc";
    } else {
        $stats['Other']++;
        $discrepancyList[] = "RID: $rid | Original at OTHER LOCATION ($reservedLot): $loc";
    }
}

echo "Status of Originals:\n";
foreach ($stats as $k => $v) {
    echo " - $k: $v\n";
}

echo "\nDiscrepancy Details (First 10):\n";
foreach (array_slice($discrepancyList, 0, 10) as $d) {
    echo " - $d\n";
}
