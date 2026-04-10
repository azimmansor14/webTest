<?php
header('Content-Type: application/json; charset=utf-8');

$csvPath = __DIR__ . '/MSIC2008_2025.csv';

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$off = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$lim = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;

if (!file_exists($csvPath)) {
    echo json_encode(['headers' => [], 'rows' => [], 'total' => 0]);
    exit;
}

$headers = [];
$filtered = [];
$qNorm = mb_strtolower($q, 'UTF-8');

if (($handle = fopen($csvPath, "r")) !== FALSE) {
    // Read the first row for headers
    $rawHeaders = fgetcsv($handle, 2000, ",");
    
    // MSIC CSV has many empty trailing columns; we filter them out
    $headers = array_values(array_filter($rawHeaders, fn($h) => !empty(trim($h))));
    $colCount = count($headers);

    while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
        // Only take data for the columns we have headers for
        $row = array_slice($data, 0, $colCount);
        
        if ($q === '') {
            $filtered[] = $row;
            continue;
        }
        
        $hit = false;
        foreach ($row as $cell) {
            if (strpos(mb_strtolower((string)$cell, 'UTF-8'), $qNorm) !== false) {
                $hit = true;
                break;
            }
        }
        if ($hit) $filtered[] = $row;
    }
    fclose($handle);
}

echo json_encode([
    'headers' => $headers,
    'rows' => array_slice($filtered, $off, $lim),
    'total' => count($filtered),
    'offset' => $off,
    'limit' => $lim
]);