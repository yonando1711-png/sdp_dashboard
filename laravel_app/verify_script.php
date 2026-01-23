<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Manually invoke the generator
$generator = new \App\Services\SummaryGenerator();
$file = '/home/yudi/dev/sdp_dashboard/LotSerial Summary_20260119.xlsx'; // Absolute path to user file

if (!file_exists($file)) {
    echo "File not found: $file\n";
    exit(1);
}

echo "Generating summary for $file...\n";

try {
    $summary = $generator->generate($file);
    
    echo "Summary Generated Successfully!\n";
    echo "--------------------------------------------------\n";
    echo "Vendor Rent: " . $summary['vendor_rent'] . "\n";
    echo "SDP Stock: " . $summary['sdp_stock'] . "\n";
    echo "In Stock Total: " . $summary['in_stock']['total'] . "\n";
    if (isset($summary['in_stock']['details']['SDP/STOCK SOLD'])) {
         print_r($summary['in_stock']['details']['SDP/STOCK SOLD']);
    }
    
    echo "Rented in Customer Total: " . $summary['rented_in_customer']['total'] . "\n";
    echo "External Service Total: " . $summary['stock_external_service']['total'] . "\n";
    echo "Internal Service Total: " . $summary['stock_internal_service']['total'] . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
