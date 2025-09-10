<?php
// Lightweight Awards API with JSON storage

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$rootDir = dirname(__DIR__);
$dataDir = $rootDir . DIRECTORY_SEPARATOR . 'data';
$dbFile = $dataDir . DIRECTORY_SEPARATOR . 'awards.json';

if (!is_dir($dataDir)) { @mkdir($dataDir, 0777, true); }
if (!file_exists($dbFile)) { file_put_contents($dbFile, json_encode([ 'auto_id' => 1, 'awards' => [] ], JSON_PRETTY_PRINT)); }

function respond_ok($payload = []) { echo json_encode(array_merge([ 'success' => true ], $payload)); exit; }
function respond_err($message, $extra = []) { echo json_encode(array_merge([ 'success' => false, 'message' => $message ], $extra)); exit; }

function load_db($dbFile) {
    $raw = @file_get_contents($dbFile);
    $data = json_decode($raw, true);
    if (!is_array($data)) { $data = [ 'auto_id' => 1, 'awards' => [] ]; }
    if (!isset($data['auto_id'])) { $data['auto_id'] = 1; }
    if (!isset($data['awards']) || !is_array($data['awards'])) { $data['awards'] = []; }
    return $data;
}

function save_db($dbFile, $data) { file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT)); }

function normalize_date($d) {
    if (!$d) return '';
    $ts = strtotime($d);
    if ($ts === false) return '';
    return date('Y-m-d', $ts);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_all');

try {
    $db = load_db($dbFile);

    if ($action === 'add') {
        $facultyName = trim((string)($_POST['faculty_name'] ?? ''));
        $awardTitle = trim((string)($_POST['award_title'] ?? ''));
        $dateReceived = normalize_date($_POST['date_received'] ?? '');
        $awardingBody = trim((string)($_POST['awarding_body'] ?? ''));
        $description = (string)($_POST['description'] ?? '');

        if ($facultyName === '' || $awardTitle === '') {
            respond_err('faculty_name and award_title are required');
        }

        $id = $db['auto_id']++;
        $now = date('Y-m-d H:i:s');
        $record = [
            'id' => $id,
            'faculty_name' => htmlspecialchars($facultyName, ENT_QUOTES, 'UTF-8'),
            'award_title' => htmlspecialchars($awardTitle, ENT_QUOTES, 'UTF-8'),
            'date_received' => $dateReceived ?: null,
            'awarding_body' => htmlspecialchars($awardingBody, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'created_at' => $now,
            'updated_at' => $now
        ];
        $db['awards'][] = $record;
        save_db($dbFile, $db);
        respond_ok([ 'award' => $record ]);
    }

    if ($action === 'get_all') {
        respond_ok([ 'awards' => array_values($db['awards']) ]);
    }

    if ($action === 'get_recent') {
        $limit = max(1, intval($_GET['limit'] ?? 5));
        $items = array_values($db['awards']);
        usort($items, function($a, $b){ return strcmp(($b['date_received'] ?? ''), ($a['date_received'] ?? '')); });
        $items = array_slice($items, 0, $limit);
        respond_ok([ 'awards' => $items ]);
    }

    if ($action === 'get_awards_by_month') {
        $year = intval($_GET['year'] ?? date('Y'));
        $counts = [];
        for ($m = 1; $m <= 12; $m++) { $counts[$m] = 0; }
        foreach ($db['awards'] as $a) {
            $d = $a['date_received'] ?? '';
            if (!$d) continue;
            $ts = strtotime($d);
            if ($ts === false) continue;
            if (intval(date('Y', $ts)) !== $year) continue;
            $monthIdx = intval(date('n', $ts));
            if ($monthIdx >= 1 && $monthIdx <= 12) { $counts[$monthIdx]++;
            }
        }
        respond_ok([ 'data' => $counts ]);
    }

    respond_err('Unknown action');
} catch (Throwable $e) {
    respond_err('Server error', [ 'error' => $e->getMessage() ]);
}


