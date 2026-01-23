<?php
require 'vendor/autoload.php';

$itemsPath = 'storage/app/private/items.json';
$items = json_decode(file_get_contents($itemsPath), true);

$rentedTotal = 0;
$rentedSubscription = 0;
$rentedNonSubscription = 0;

foreach ($items as $item) {
    if (!empty($item['is_sold'])) continue;
    if ($item['location'] !== 'Partners/Customers/Rental') continue;

    $rentedTotal++;
    if (($item['rental_type'] ?? '') === 'Subscription') {
        $rentedSubscription++;
    } else {
        $rentedNonSubscription++;
    }
}

echo "Total Rented In Customer: $rentedTotal\n";
echo "Subscription: $rentedSubscription\n";
echo "Non-Subscription: $rentedNonSubscription\n";
