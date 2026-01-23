<?php
require 'vendor/autoload.php';

$itemsPath = 'storage/app/private/items.json';

if (!file_exists($itemsPath)) {
    echo "Files not found.\n";
    exit(1);
}

$content = file_get_contents($itemsPath);
$items = json_decode($content, true);

$rentalIdCounts = [];
foreach ($items as $item) {
    if (!empty($item['rental_id'])) {
        $rid = $item['rental_id'];
        $rentalIdCounts[$rid] = ($rentalIdCounts[$rid] ?? 0) + 1;
    }
}

$counts = [
    'Subscription' => 0,
    'Regular' => 0,
    'Other' => 0
];

$locations = [
    'Subscription' => [],
    'Regular' => []
];

foreach ($items as $item) {
    if (!empty($item['is_sold'])) continue;
    
    $type = $item['rental_type'] ?? '';
    if ($type !== 'Subscription') continue;

    $loc = $item['location'] ?? 'Unknown';
    $rentalId = $item['rental_id'] ?? '';
    $lotNo = $item['lot_number'] ?? '';
    $reservedLot = $item['reserved_lot'] ?? '';
    
    // Determine detailed category
    $cat = 'Unknown';
    
    if ($loc == 'Partners/Customers/Rental') {
        $cat = 'Rented in Customer';
    } elseif (stripos($loc, 'Partners/Vendors/Service') === 0 || $loc == 'Physical Locations/Service' || stripos($loc, 'Partners/Vendors/Insurance') === 0) {
        $cat = 'In Service';
        // Sub-details
        if (!empty($rentalId) && $lotNo == $reservedLot) {
             $ridCount = $rentalIdCounts[$rentalId] ?? 0;
             if ($ridCount > 1) {
                 $cat .= ' - Original with Replace';
             } else {
                 $cat .= ' - Original without Replace';
             }
        } else {
             $cat .= ' - Replacement/Other';
        }
    } elseif (!empty($item['in_stock'])) {
        $cat = 'In Stock';
        if (!empty($rentalId)) {
            // Check start date for original vs reserve
           $startDate = $item['actual_start_rental'] ?? null;
           $todaySerial = 46044; // Approx today
           if ($startDate <= $todaySerial) {
               $cat .= ' - Original (Active)';
           } else {
               $cat .= ' - Reserve (Future)';
           }
        } else {
            $cat .= ' - Pure';
        }
    }

    if (!isset($locations[$cat])) $locations[$cat] = 0;
    $locations[$cat]++;
}

ksort($locations);
echo "Detailed Breakdown of Subscription Items:\n";
foreach ($locations as $cat => $count) {
    echo "  - $cat: $count\n";
}
