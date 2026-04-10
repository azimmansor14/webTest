<?php
header('Content-Type: application/json; charset=utf-8');

// Path to the full MSBR data
$csvPath = __DIR__ . '/newMSBR.csv';

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$off = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$lim = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;

function norm($s) {
    return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string)$s)), 'UTF-8');
}

if (!file_exists($csvPath)) {
    echo json_encode(['headers' => [], 'rows' => [], 'total' => 0, 'error' => 'File not found']);
    exit;
}

$headers = [];
$filtered = [];
$qNorm = norm($q);

if (($handle = fopen($csvPath, "r")) !== FALSE) {
    $headers = fgetcsv($handle, 4000, ",");
    
    // Define columns to search in (Names and IDs)
    $searchIndices = [];
    $searchColumns = ['No. Siri', 'Nama Pendaftaran', 'Nama Perniagaan', 'NGDBBP Tahunan'];
    foreach($searchColumns as $col) {
        $idx = array_search($col, $headers);
        if ($idx !== false) $searchIndices[] = $idx;
    }

    while (($data = fgetcsv($handle, 4000, ",")) !== FALSE) {
        if ($qNorm === '') {
            $filtered[] = $data;
            continue;
        }

        $hit = false;
        foreach ($searchIndices as $idx) {
            if (isset($data[$idx]) && strpos(norm($data[$idx]), $qNorm) !== false) {
                $hit = true;
                break;
            }
        }
        if ($hit) $filtered[] = $data;
    }
    fclose($handle);
}

echo json_encode([
    'headers' => $headers,
    'rows' => array_slice($filtered, $off, $lim),
    'total' => count($filtered)
]);