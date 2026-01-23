<?php
$items = json_decode(file_get_contents('storage/app/private/items.json'), true);

// Find all Replacement - Service vehicles (in customer, lot != reserved, rental_id count > 1)
$replacementService = [];
foreach ($items as $item) {
    if ($item['location'] == 'Partners/Customers/Rental' 
        && !empty($item['rental_id'])
        && $item['lot_number'] != $item['reserved_lot']
        && ($item['rental_id_count'] ?? 0) > 1
        && empty($item['is_vendor_rent'])) {
        $replacementService[] = $item;
    }
}

echo "Replacement - Service vehicles: " . count($replacementService) . "\n\n";

// For each, find where the MAIN vehicle (reserved_lot) is located
$mainLocations = [];
$notFound = 0;
foreach ($replacementService as $rep) {
    $rentalId = $rep['rental_id'];
    $reservedLot = $rep['reserved_lot']; // This is the main vehicle's lot number
    
    $found = false;
    // Find the main vehicle by its lot number
    foreach ($items as $item) {
        if ($item['lot_number'] == $reservedLot) {
            $loc = $item['location'];
            $mainLocations[$loc] = ($mainLocations[$loc] ?? 0) + 1;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $notFound++;
    }
}

echo "Main vehicle locations for these replacements:\n";
arsort($mainLocations);
foreach ($mainLocations as $loc => $count) {
    echo "  $loc: $count\n";
}
echo "\nMain vehicles not found: $notFound\n";

// Summary
echo "\n=== EXPLANATION ===\n";
echo "The 40 'Replacement - Service' are replacement vehicles WITH THE CUSTOMER.\n";
echo "Their corresponding MAIN vehicles are located in VARIOUS places:\n";

$inService = 0;
foreach ($mainLocations as $loc => $count) {
    if (strpos($loc, 'Service') !== false || strpos($loc, 'Insurance') !== false) {
        $inService += $count;
    }
}
echo "\n- In Service (Ext/Int/Insurance): $inService\n";
echo "- Other locations: " . (array_sum($mainLocations) - $inService) . "\n";
