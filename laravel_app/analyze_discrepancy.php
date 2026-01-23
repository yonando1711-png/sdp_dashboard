<?php
$items = json_decode(file_get_contents('storage/app/private/items.json'), true);

echo "=== DETAILED ANALYSIS ===\n\n";

// 1. Count "Original Rented with Replace" in External Service (dashboard perspective)
$externalOrigWithReplace = [];
foreach ($items as $item) {
    if (stripos($item['location'], 'Partners/Vendors/Service') === 0
        && !empty($item['rental_id'])
        && $item['lot_number'] == $item['reserved_lot']
        && ($item['rental_id_count'] ?? 0) > 1) {
        $externalOrigWithReplace[] = $item;
    }
}
echo "External 'Original Rented with Replace' (dashboard count): " . count($externalOrigWithReplace) . "\n";

// 2. Count main vehicles of Replacement-Service that are in External Service
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

$mainInExternal = [];
foreach ($replacementService as $rep) {
    $reservedLot = $rep['reserved_lot'];
    foreach ($items as $item) {
        if ($item['lot_number'] == $reservedLot 
            && stripos($item['location'], 'Partners/Vendors/Service') === 0) {
            $mainInExternal[] = [
                'replacement_lot' => $rep['lot_number'],
                'main_lot' => $item['lot_number'],
                'rental_id' => $item['rental_id'],
                'main_location' => $item['location']
            ];
            break;
        }
    }
}
echo "Main vehicles of Rep-Service in External: " . count($mainInExternal) . "\n\n";

// Show the difference
echo "=== DIFFERENCE EXPLANATION ===\n";
echo "Dashboard counts 'Original Rented with Replace' = vehicles in service where:\n";
echo "  - lot_number == reserved_lot (MAIN vehicle)\n";
echo "  - rental_id_count > 1 (has pair somewhere)\n\n";

echo "My analysis counted main vehicles of 'Replacement-Service' customers.\n";
echo "These are NOT exactly the same!\n\n";

echo "The 21 in External Service includes ALL main vehicles with a pair ANYWHERE.\n";
echo "The 20 from my analysis only counts those whose replacement is WITH CUSTOMER.\n\n";

// Find the extra 1
echo "=== FINDING THE EXTRA 1 ===\n";
$mainLotsInExternal = array_column($mainInExternal, 'main_lot');
foreach ($externalOrigWithReplace as $item) {
    if (!in_array($item['lot_number'], $mainLotsInExternal)) {
        echo "Extra vehicle not linked to customer replacement:\n";
        echo "  Lot: " . $item['lot_number'] . "\n";
        echo "  Rental ID: " . $item['rental_id'] . "\n";
        echo "  Location: " . $item['location'] . "\n";
        
        // Find where its pair is
        foreach ($items as $pair) {
            if ($pair['rental_id'] == $item['rental_id'] && $pair['lot_number'] != $item['lot_number']) {
                echo "  Paired with: " . $pair['lot_number'] . " at " . $pair['location'] . "\n";
            }
        }
        echo "\n";
    }
}
