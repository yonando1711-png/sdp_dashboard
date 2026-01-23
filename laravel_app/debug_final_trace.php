<?php
require 'vendor/autoload.php';

$itemsPath = 'storage/app/private/items.json';
$items = json_decode(file_get_contents($itemsPath), true);

// 1. Build Index
$itemsByRentalId = [];
foreach ($items as $item) {
    if (!empty($item['rental_id'])) {
        $rid = $item['rental_id'];
        $itemsByRentalId[$rid][] = $item;
    }
}

// 2. Identify the "1 Difference" in Service
// We look for any Original in Service whose Replacement is NOT 'Partners/Customers/Rental'.
$missingOriginal = [];
foreach ($items as $item) {
    if (!empty($item['is_sold'])) continue;
    
    // Check if item is Original in Service
    $loc = $item['location'];
    $inService = (stripos($loc, 'Partners/Vendors/Service') === 0 || $loc == 'Physical Locations/Service');
    
    if ($inService) {
        $rid = $item['rental_id'];
        $lotNo = $item['lot_number'];
        $reservedLot = $item['reserved_lot'];
        
        if (!empty($rid) && $lotNo == $reservedLot) {
            // This is an Original in Service.
            // Check if it has a replacement
            $group = $itemsByRentalId[$rid] ?? [];
            if (count($group) > 1) {
                // It has a replacement in the system.
                // Check if any replacement is with Customer
                $hasCustomerReplacement = false;
                foreach ($group as $member) {
                    if ($member['lot_number'] != $reservedLot && $member['location'] == 'Partners/Customers/Rental') {
                        $hasCustomerReplacement = true;
                        break;
                    }
                }
                
                if (!$hasCustomerReplacement) {
                    $missingOriginal[] = $item;
                }
            }
        }
    }
}

echo "1. The '1 Difference' (Original in Service but Replacement NOT with Customer):\n";
foreach ($missingOriginal as $diff) {
    echo " - Lot: " . $diff['lot_number'] . " | RID: " . $diff['rental_id'] . " | Location: " . $diff['location'] . "\n";
    // Find where the replacement is
    $rid = $diff['rental_id'];
    $group = $itemsByRentalId[$rid] ?? [];
    foreach ($group as $member) {
        if ($member['lot_number'] != $diff['lot_number']) {
            echo "   -> Replacement Lot: " . $member['lot_number'] . " | Location: " . $member['location'] . "\n";
        }
    }
}
echo "\n";

// 3. Explain the 10 Item Difference in Stock
// "In Stock" card shows 28 Originals.
// My previous trace found 18 Originals linked to Customer Replacements.
// The difference (10) should be Originals in Stock whose replacements are NOT with Customer.

$stockOriginalsNonCustomerRep = [];
foreach ($items as $item) {
    if (!empty($item['is_sold'])) continue;
    if (empty($item['in_stock'])) continue; // Must be in stock
    
    $rid = $item['rental_id'];
    $lotNo = $item['lot_number'];
    $reservedLot = $item['reserved_lot'];
    
    if (!empty($rid) && $lotNo == $reservedLot) {
        // Only count if start date is valid (Active)
        $startDate = $item['actual_start_rental'] ?? null;
        $todaySerial = 46044; 
        if ($startDate > $todaySerial) continue; // Reserve, not Active Original
        
        // Check replacements
        $group = $itemsByRentalId[$rid] ?? [];
        $hasCustomerReplacement = false;
        foreach ($group as $member) {
            if ($member['lot_number'] != $reservedLot && $member['location'] == 'Partners/Customers/Rental') {
                $hasCustomerReplacement = true;
                break;
            }
        }
        
        if (!$hasCustomerReplacement) {
            $stockOriginalsNonCustomerRep[] = $item;
        }
    }
}

echo "2. The '10 Difference' in Stock (Originals in Stock but Replacement NOT with Customer):\n";
echo "Count: " . count($stockOriginalsNonCustomerRep) . "\n";
foreach (array_slice($stockOriginalsNonCustomerRep, 0, 10) as $stk) {
    echo " - Lot: " . $stk['lot_number'] . " | RID: " . $stk['rental_id'] . "\n";
    // Find where replacement is
    $rid = $stk['rental_id'];
    $group = $itemsByRentalId[$rid] ?? [];
    foreach ($group as $member) {
        if ($member['lot_number'] != $stk['lot_number']) {
            echo "   -> Replacement Lot: " . $member['lot_number'] . " | Location: " . $member['location'] . "\n";
        }
    }
}
