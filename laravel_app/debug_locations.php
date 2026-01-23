<?php
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = '/home/yudi/dev/sdp_dashboard/LotSerial Summary_20260119.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = null;
foreach ($spreadsheet->getAllSheets() as $s) {
    if ($s->getTitle() == 'Sheet1') { // Try by name if possible
        $sheet = $s;
        break;
    }
}
if (!$sheet) {
    // try finding by column
    foreach ($spreadsheet->getAllSheets() as $s) {
        $row = $s->getRowIterator()->current();
        // This is hard to iterate. Use toArray
        $data = $s->toArray();
        if (in_array('Product', array_map('strval', $data[0] ?? []))) {
            $sheet = $s; 
            break;
        }
    }
}

if (!$sheet) die("Sheet not found");

$data = $sheet->toArray();
$header = array_shift($data);
$header = array_map('strval', $header);
$colMap = array_flip($header);
$locIdx = $colMap['Location'] ?? -1;

if ($locIdx == -1) die("Location column not found");

$locations = [];
foreach ($data as $row) {
    $loc = trim($row[$locIdx] ?? '');
    if ($loc) $locations[$loc] = ($locations[$loc] ?? 0) + 1;
}

print_r($locations);
