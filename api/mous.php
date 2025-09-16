<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Paths
$rootDir = dirname(__DIR__);
$dataDir = $rootDir . DIRECTORY_SEPARATOR . 'data';
$uploadsDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads';
$dbFile = $dataDir . DIRECTORY_SEPARATOR . 'mous.json';

// Ensure storage exists
if (!is_dir($dataDir)) { @mkdir($dataDir, 0777, true); }
if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0777, true); }
if (!file_exists($dbFile)) { 
    file_put_contents($dbFile, json_encode(['auto_id' => 1, 'mous' => []])); 
}

function load_db($dbFile) {
    $raw = @file_get_contents($dbFile);
    if ($raw === false) {
        error_log("Failed to read mous.json file");
        return ['auto_id' => 1, 'mous' => []];
    }
    
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in mous.json: " . json_last_error_msg());
        return ['auto_id' => 1, 'mous' => []];
    }
    
    if (!is_array($data)) { $data = ['auto_id' => 1, 'mous' => []]; }
    if (!isset($data['auto_id'])) { $data['auto_id'] = 1; }
    if (!isset($data['mous'])) { $data['mous'] = []; }
    
    return $data;
}

function save_db($dbFile, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    if ($json === false) {
        error_log("JSON encode error: " . json_last_error_msg());
        return false;
    }
    
    $result = @file_put_contents($dbFile, $json);
    if ($result === false) {
        error_log("Failed to write mous.json file");
        return false;
    }
    
    return true;
}

function mous_respond($ok, $payload = []) {
    echo json_encode(array_merge(['success' => $ok], $payload));
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_all');

if ($action === 'add') {
    $db = load_db($dbFile);
    
    // Get form data
    $institution = trim($_POST['institution'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $term = trim($_POST['term'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $dateSigned = trim($_POST['date_signed'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate required fields
    if (empty($institution) || empty($location) || empty($contact) || empty($term) || 
        empty($type) || empty($status) || empty($dateSigned) || empty($endDate) || empty($description)) {
        mous_respond(false, ['message' => 'All fields are required']);
    }
    
    // Handle file upload
    $fileName = null;
    if (isset($_FILES['mou-file']) && $_FILES['mou-file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $uploadsDir . DIRECTORY_SEPARATOR . 'mous';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $originalName = $_FILES['mou-file']['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = 'mou_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        
        if (!move_uploaded_file($_FILES['mou-file']['tmp_name'], $filePath)) {
            mous_respond(false, ['message' => 'Failed to upload file']);
        }
    }
    
    // Create new MOU record
    $id = $db['auto_id']++;
    $now = date('Y-m-d H:i:s');
    
    $mou = [
        'id' => $id,
        'institution' => htmlspecialchars($institution, ENT_QUOTES, 'UTF-8'),
        'location' => htmlspecialchars($location, ENT_QUOTES, 'UTF-8'),
        'contact' => htmlspecialchars($contact, ENT_QUOTES, 'UTF-8'),
        'term' => htmlspecialchars($term, ENT_QUOTES, 'UTF-8'),
        'type' => htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
        'status' => htmlspecialchars($status, ENT_QUOTES, 'UTF-8'),
        'date_signed' => $dateSigned,
        'end_date' => $endDate,
        'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
        'file_name' => $fileName,
        'created_at' => $now,
        'updated_at' => $now
    ];
    
    $db['mous'][] = $mou;
    
    if (save_db($dbFile, $db)) {
        mous_respond(true, ['message' => 'MOU/MOA created successfully', 'newMouId' => $id, 'mou' => $mou]);
    } else {
        mous_respond(false, ['message' => 'Failed to save MOU/MOA']);
    }
}

if ($action === 'get_all' || $action === 'list') {
    $db = load_db($dbFile);
    mous_respond(true, ['mous' => $db['mous'], 'documents' => $db['mous']]);
}

if ($action === 'get_stats') {
    $db = load_db($dbFile);
    $mous = $db['mous'];
    
    $total = count($mous);
    $active = 0;
    $expiringSoon = 0;
    
    $today = new DateTime();
    $soon = new DateTime();
    $soon->add(new DateInterval('P30D')); // 30 days from now
    
    foreach ($mous as $mou) {
        if ($mou['status'] === 'Active') {
            $active++;
            
            if ($mou['end_date']) {
                $endDate = new DateTime($mou['end_date']);
                if ($endDate <= $soon && $endDate >= $today) {
                    $expiringSoon++;
                }
            }
        }
    }
    
    $stats = [
        'total' => $total,
        'active' => $active,
        'expiringSoon' => $expiringSoon
    ];
    
    mous_respond(true, ['stats' => $stats]);
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        mous_respond(false, ['message' => 'Invalid MOU ID']);
    }
    
    $db = load_db($dbFile);
    $found = false;
    
    foreach ($db['mous'] as $index => $mou) {
        if ($mou['id'] == $id) {
            // Delete associated file if exists
            if ($mou['file_name']) {
                $filePath = $uploadsDir . DIRECTORY_SEPARATOR . 'mous' . DIRECTORY_SEPARATOR . $mou['file_name'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            array_splice($db['mous'], $index, 1);
            $found = true;
        break;
        }
    }
    
    if ($found && save_db($dbFile, $db)) {
        mous_respond(true, ['message' => 'MOU/MOA deleted successfully']);
    } else {
        mous_respond(false, ['message' => 'MOU/MOA not found or failed to delete']);
    }
}

// Default response
mous_respond(false, ['message' => 'Invalid action']);
?>
