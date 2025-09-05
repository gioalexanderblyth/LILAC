<?php
// Lightweight Documents API with JSON storage and file uploads
// Paths
$rootDir = dirname(__DIR__);
$dataDir = $rootDir . DIRECTORY_SEPARATOR . 'data';
$uploadsDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads';
$dbFile = $dataDir . DIRECTORY_SEPARATOR . 'documents.json';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function respond($ok, $payload = []) {
    echo json_encode(array_merge(['success' => $ok], $payload));
    exit;
}

// Ensure storage exists
if (!is_dir($dataDir)) { @mkdir($dataDir, 0777, true); }
if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0777, true); }
if (!file_exists($dbFile)) { file_put_contents($dbFile, json_encode(['auto_id' => 1, 'documents' => []])); }

function load_db($dbFile) {
    $raw = @file_get_contents($dbFile);
    $data = json_decode($raw, true);
    if (!is_array($data)) { $data = ['auto_id' => 1, 'documents' => []]; }
    if (!isset($data['auto_id'])) { $data['auto_id'] = 1; }
    if (!isset($data['documents']) || !is_array($data['documents'])) { $data['documents'] = []; }
    return $data;
}

function save_db($dbFile, $data) {
    file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT));
}

function sanitize_filename($name) {
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    return trim($name, '_');
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_all');

try {
    $db = load_db($dbFile);

    if ($action === 'add') {
        // Validate file
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            respond(false, ['message' => 'No file uploaded']);
        }
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            respond(false, ['message' => 'Upload error: ' . $_FILES['file']['error']]);
        }
        if (intval($_FILES['file']['size']) <= 0) {
            respond(false, ['message' => 'File size is zero']);
        }

        $originalName = $_FILES['file']['name'];
        $safeName = sanitize_filename($originalName);
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','txt','jpg','jpeg','png'];
        if ($ext && !in_array($ext, $allowed, true)) {
            respond(false, ['message' => 'File type not allowed']);
        }

        // Unique destination
        $unique = uniqid('doc_', true) . ($ext ? ('.' . $ext) : '');
        $dest = $uploadsDir . DIRECTORY_SEPARATOR . $unique;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            respond(false, ['message' => 'Failed to save uploaded file']);
        }

        $documentName = $_POST['document_name'] ?? ($_POST['title'] ?? pathinfo($safeName, PATHINFO_FILENAME));
        $category = $_POST['category'] ?? '';
        $description = $_POST['description'] ?? '';

        $now = date('Y-m-d H:i:s');
        $id = $db['auto_id']++;

        $record = [
            'id' => $id,
            'document_name' => htmlspecialchars($documentName, ENT_QUOTES, 'UTF-8'),
            'filename' => $unique,
            'original_filename' => $safeName,
            'file_size' => intval(filesize($dest)),
            'category' => htmlspecialchars($category, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'upload_date' => $now,
            'status' => 'Active'
        ];
        $db['documents'][] = $record;
        save_db($dbFile, $db);
        respond(true, ['message' => 'Document added', 'document' => $record]);
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { respond(false, ['message' => 'Invalid id']); }
        $found = false;
        foreach ($db['documents'] as $i => $doc) {
            if (intval($doc['id']) === $id) {
                $found = true;
                $filepath = $uploadsDir . DIRECTORY_SEPARATOR . ($doc['filename'] ?? '');
                if (is_file($filepath)) { @unlink($filepath); }
                array_splice($db['documents'], $i, 1);
                break;
            }
        }
        if (!$found) { respond(false, ['message' => 'Document not found']); }
        save_db($dbFile, $db);
        respond(true, ['message' => 'Deleted']);
    }

    if ($action === 'get_categories') {
        $cats = [];
        foreach ($db['documents'] as $doc) {
            if (!empty($doc['category'])) { $cats[$doc['category']] = true; }
        }
        respond(true, ['categories' => array_values(array_keys($cats))]);
    }

    if ($action === 'get_stats' || $action === 'get_stats_by_category') {
        $category = $_GET['category'] ?? '';
        $filtered = $db['documents'];
        if ($action === 'get_stats_by_category' && $category !== '') {
            $filtered = array_values(array_filter($filtered, function ($d) use ($category) {
                return isset($d['category']) && $d['category'] === $category;
            }));
        }
        $total = count($filtered);
        $totalSize = array_sum(array_map(function ($d) { return intval($d['file_size'] ?? 0); }, $filtered));
        respond(true, ['stats' => [
            'total_documents' => $total,
            'total_size' => $totalSize,
        ]]);
    }

    if ($action === 'send_email_share') {
        // Minimal stub: log to email_log.txt
        $logFile = $rootDir . DIRECTORY_SEPARATOR . 'email_log.txt';
        $to = $_POST['email'] ?? '';
        $docs = $_POST['documents'] ?? '[]';
        $entry = date('c') . " | To: {$to} | Docs: {$docs}\n";
        file_put_contents($logFile, $entry, FILE_APPEND);
        respond(true, ['message' => 'Share logged']);
    }

    // get_all with pagination, search, category, sorting
    if ($action === 'get_all' || $action === 'get_by_category') {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
        $search = trim((string)($_GET['search'] ?? ''));
        $category = ($action === 'get_by_category') ? (string)($_GET['category'] ?? '') : (string)($_GET['category'] ?? '');
        $sortBy = $_GET['sort_by'] ?? 'upload_date';
        $sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');

        $docs = $db['documents'];
        if ($category !== '') {
            $docs = array_values(array_filter($docs, function ($d) use ($category) {
                return isset($d['category']) && $d['category'] === $category;
            }));
        }
        if ($search !== '') {
            $q = mb_strtolower($search);
            $docs = array_values(array_filter($docs, function ($d) use ($q) {
                $hay = mb_strtolower(($d['document_name'] ?? '') . ' ' . ($d['filename'] ?? '') . ' ' . ($d['description'] ?? ''));
                return strpos($hay, $q) !== false;
            }));
        }
        // Sorting
        usort($docs, function ($a, $b) use ($sortBy, $sortOrder) {
            $va = $a[$sortBy] ?? '';
            $vb = $b[$sortBy] ?? '';
            if ($sortBy === 'file_size') { $va = intval($va); $vb = intval($vb); }
            $cmp = $va <=> $vb;
            return ($sortOrder === 'DESC') ? -$cmp : $cmp;
        });

        $total = count($docs);
        $offset = ($page - 1) * $limit;
        $paged = array_slice($docs, $offset, $limit);

        respond(true, [
            'documents' => $paged,
            'pagination' => [
                'total_documents' => $total,
                'current_page' => $page,
                'limit' => $limit,
                'total_pages' => (int)ceil($total / $limit)
            ]
        ]);
    }

    respond(false, ['message' => 'Unknown action']);
} catch (Throwable $e) {
    respond(false, ['message' => 'Server error', 'error' => $e->getMessage()]);
}


