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
if (!file_exists($dbFile)) { file_put_contents($dbFile, json_encode(['auto_id' => 1, 'documents' => [], 'trash' => []])); }

function load_db($dbFile) {
    $raw = @file_get_contents($dbFile);
    $data = json_decode($raw, true);
    if (!is_array($data)) { $data = ['auto_id' => 1, 'documents' => []]; }
    if (!isset($data['auto_id'])) { $data['auto_id'] = 1; }
    if (!isset($data['documents']) || !is_array($data['documents'])) { $data['documents'] = []; }
    if (!isset($data['trash']) || !is_array($data['trash'])) { $data['trash'] = []; }
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

    // Simple auto-category detection based on filename/title keywords
    function detect_category_from_name($name) {
        $upper = strtoupper((string)$name);
        // MOU/MOA patterns
        if (preg_match('/\b(MOU|MOA|MEMORANDUM OF UNDERSTANDING|AGREEMENT|KUMA-MOU)\b/i', $name)) {
            return 'MOUs & MOAs';
        }
        // Registrar patterns
        if (preg_match('/\b(REGISTRAR|TRANSCRIPT|TOR|CERTIFICATE|COR|GWA|GRADES|ENROLLMENT|STUDENT\s*RECORD)\b/i', $name)) {
            return 'Registrar Files';
        }
        // Template / Form patterns
        if (preg_match('/\b(TEMPLATE|FORM|FORMS|ADMISSION|APPLICATION|REGISTRATION|CHECKLIST|REQUEST)\b/i', $name)) {
            return 'Templates';
        }
        return '';
    }

    function detect_category_from_text($text) {
        if (!$text) return '';
        if (preg_match('/\b(MOU|MOA|MEMORANDUM OF UNDERSTANDING|AGREEMENT|PARTNERSHIP|RENEWAL|KUMA-MOU)\b/i', $text)) return 'MOUs & MOAs';
        if (preg_match('/\b(REGISTRAR|ENROLLMENT|TRANSCRIPT|TOR|CERTIFICATE|COR|STUDENT\s*RECORD|GWA|GRADES)\b/i', $text)) return 'Registrar Files';
        if (preg_match('/\b(TEMPLATE|FORM|ADMISSION|APPLICATION|REGISTRATION|CHECKLIST|REQUEST)\b/i', $text)) return 'Templates';
        return '';
    }

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
            // Still allow saving, but mark as other types
        }

        // Unique destination
        $unique = uniqid('doc_', true) . ($ext ? ('.' . $ext) : '');
        $dest = $uploadsDir . DIRECTORY_SEPARATOR . $unique;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            respond(false, ['message' => 'Failed to save uploaded file']);
        }

        $documentName = $_POST['document_name'] ?? ($_POST['title'] ?? pathinfo($safeName, PATHINFO_FILENAME));
        $category = trim((string)($_POST['category'] ?? ''));
        $categoryConfidence = floatval($_POST['category_confidence'] ?? 0);
        
        if ($category === '') {
            $auto = detect_category_from_name($documentName . ' ' . $safeName);
            if ($auto !== '') { 
                $category = $auto;
                $categoryConfidence = 0.5; // Default confidence for server-side detection
            }
        }
        $description = $_POST['description'] ?? '';

        // OCR text (optional excerpt from client)
        $ocrText = $_POST['ocr_excerpt'] ?? '';
        if ($category === '' && $ocrText !== '') {
            $fallback = detect_category_from_text($ocrText);
            if ($fallback !== '') { 
                $category = $fallback;
                $categoryConfidence = 0.3; // Lower confidence for OCR-based detection
            }
        }

        $now = date('Y-m-d H:i:s');
        $id = $db['auto_id']++;

        $record = [
            'id' => $id,
            'document_name' => htmlspecialchars($documentName, ENT_QUOTES, 'UTF-8'),
            'filename' => $unique,
            'original_filename' => $safeName,
            'file_size' => intval(filesize($dest)),
            'category' => htmlspecialchars($category, ENT_QUOTES, 'UTF-8'),
            'category_confidence' => $categoryConfidence,
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'upload_date' => $now,
            'status' => 'Active',
            'ocr_text' => $ocrText
        ];
        $db['documents'][] = $record;
        save_db($dbFile, $db);
        respond(true, ['message' => 'Document added', 'document' => $record]);
    }

    if ($action === 'reclassify_auto') {
        $updated = 0;
        foreach ($db['documents'] as &$doc) {
            $name = ($doc['document_name'] ?? '') . ' ' . ($doc['original_filename'] ?? '');
            $auto = detect_category_from_name($name);
            if ($auto === '' && !empty($doc['ocr_text'])) { $auto = detect_category_from_text($doc['ocr_text']); }
            if ($auto !== '' && ($doc['category'] ?? '') !== $auto) {
                $doc['category'] = $auto;
                $updated++;
            }
        }
        unset($doc);
        if ($updated > 0) { save_db($dbFile, $db); }
        respond(true, ['message' => 'Reclassification complete', 'updated' => $updated]);
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { respond(false, ['message' => 'Invalid id']); }
        $found = false;
        foreach ($db['documents'] as $i => $doc) {
            if (intval($doc['id']) === $id) {
                $found = true;
                // Move to trash instead of permanently deleting
                $removed = $doc;
                $removed['original_id'] = $doc['id'];
                $removed['deleted_at'] = date('c');
                // Ensure unique trash id
                $removed['id'] = $db['auto_id']++;
                $db['trash'][] = $removed;
                array_splice($db['documents'], $i, 1);
                break;
            }
        }
        if (!$found) { respond(false, ['message' => 'Document not found']); }
        save_db($dbFile, $db);
        respond(true, ['message' => 'Moved to trash']);
    }

    if ($action === 'get_trash') {
        respond(true, ['trash' => array_values($db['trash'])]);
    }

    if ($action === 'restore') {
        $trashId = intval($_POST['trash_id'] ?? 0);
        if ($trashId <= 0) { respond(false, ['message' => 'Invalid trash id']); }
        foreach ($db['trash'] as $i => $t) {
            if (intval($t['id']) === $trashId || intval($t['original_id'] ?? 0) === $trashId) {
                $restored = $t;
                unset($restored['deleted_at']);
                unset($restored['original_id']);
                // Assign new id to avoid collisions
                $restored['id'] = $db['auto_id']++;
                $db['documents'][] = $restored;
                array_splice($db['trash'], $i, 1);
                save_db($dbFile, $db);
                respond(true, ['message' => 'Restored']);
            }
        }
        respond(false, ['message' => 'Trash item not found']);
    }

    if ($action === 'permanently_delete') {
        $trashId = intval($_POST['trash_id'] ?? 0);
        if ($trashId <= 0) { respond(false, ['message' => 'Invalid trash id']); }
        foreach ($db['trash'] as $i => $t) {
            if (intval($t['id']) === $trashId || intval($t['original_id'] ?? 0) === $trashId) {
                // Also remove uploaded file if exists
                $filepath = $uploadsDir . DIRECTORY_SEPARATOR . ($t['filename'] ?? '');
                if (is_file($filepath)) { @unlink($filepath); }
                array_splice($db['trash'], $i, 1);
                save_db($dbFile, $db);
                respond(true, ['message' => 'Permanently deleted']);
            }
        }
        respond(false, ['message' => 'Trash item not found']);
    }

    if ($action === 'empty_trash') {
        // Delete files for all trash items then clear
        foreach ($db['trash'] as $t) {
            $filepath = $uploadsDir . DIRECTORY_SEPARATOR . ($t['filename'] ?? '');
            if (is_file($filepath)) { @unlink($filepath); }
        }
        $db['trash'] = [];
        save_db($dbFile, $db);
        respond(true, ['message' => 'Trash emptied']);
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
        // derive simple recency metrics for UI compatibility
        $now = time();
        $thirtyDaysAgo = $now - (30 * 24 * 60 * 60);
        $startOfMonth = strtotime(date('Y-m-01 00:00:00'));
        $recentCount = 0;
        $monthCount = 0;
        foreach ($filtered as $d) {
            $ts = isset($d['upload_date']) ? strtotime($d['upload_date']) : 0;
            if ($ts && $ts >= $thirtyDaysAgo) { $recentCount++; }
            if ($ts && $ts >= $startOfMonth) { $monthCount++; }
        }
        respond(true, ['stats' => [
            'total_documents' => $total,
            'total' => $total,
            'recent' => $recentCount,
            'month' => $monthCount,
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

        // Advanced filters
        $fileGroup = $_GET['file_group'] ?? 'all';
        $fileTypes = [];
        if (isset($_GET['file_type']) && is_array($_GET['file_type'])) { $fileTypes = $_GET['file_type']; }
        $categoriesMulti = (isset($_GET['category']) && is_array($_GET['category'])) ? $_GET['category'] : [];
        $dateFrom = isset($_GET['date_from']) ? strtotime($_GET['date_from'].' 00:00:00') : 0;
        $dateTo = isset($_GET['date_to']) ? strtotime($_GET['date_to'].' 23:59:59') : 0;
        $sizeMin = isset($_GET['size_min']) ? intval($_GET['size_min']) : 0;
        $sizeMax = isset($_GET['size_max']) ? intval($_GET['size_max']) : 0;

        $docs = $db['documents'];

        // Category single (legacy)
        if ($category !== '' && !is_array($category)) {
            $docs = array_values(array_filter($docs, function ($d) use ($category) {
                return isset($d['category']) && $d['category'] === $category;
            }));
        }
        // Category multi
        if (!empty($categoriesMulti)) {
            $set = array_flip($categoriesMulti);
            $docs = array_values(array_filter($docs, function ($d) use ($set) {
                $cat = $d['category'] ?? '';
                return $cat !== '' && isset($set[$cat]);
            }));
        }
        // Search (include ocr_text)
        if ($search !== '') {
            $q = mb_strtolower($search);
            $docs = array_values(array_filter($docs, function ($d) use ($q) {
                $hay = mb_strtolower(($d['document_name'] ?? '') . ' ' . ($d['filename'] ?? '') . ' ' . ($d['description'] ?? '') . ' ' . ($d['ocr_text'] ?? ''));
                return strpos($hay, $q) !== false;
            }));
        }
        // File group mapping
        if ($fileGroup !== 'all') {
            $groupMap = [
                'documents' => ['doc','docx','txt','rtf'],
                'spreadsheets' => ['xls','xlsx','csv'],
                'pdfs' => ['pdf'],
                'images' => ['jpg','jpeg','png','gif','webp']
            ];
            $exts = $groupMap[$fileGroup] ?? [];
            if (!empty($exts)) {
                $set = array_flip($exts);
                $docs = array_values(array_filter($docs, function ($d) use ($set) {
                    $ext = strtolower(pathinfo($d['filename'] ?? '', PATHINFO_EXTENSION));
                    return $ext && isset($set[$ext]);
                }));
            }
        }
        // Explicit file types
        if (!empty($fileTypes)) {
            $explicit = [];
            foreach ($fileTypes as $ft) {
                $parts = explode('|', (string)$ft);
                foreach ($parts as $p) {
                    $p = strtolower(trim($p));
                    if ($p !== '') { $explicit[$p] = true; }
                }
            }
            if (!empty($explicit)) {
                $docs = array_values(array_filter($docs, function ($d) use ($explicit) {
                    $ext = strtolower(pathinfo($d['filename'] ?? '', PATHINFO_EXTENSION));
                    return $ext && isset($explicit[$ext]);
                }));
            }
        }
        // Date range
        if ($dateFrom || $dateTo) {
            $docs = array_values(array_filter($docs, function ($d) use ($dateFrom, $dateTo) {
                $ts = isset($d['upload_date']) ? strtotime($d['upload_date']) : 0;
                if ($dateFrom && $ts < $dateFrom) return false;
                if ($dateTo && $ts > $dateTo) return false;
                return true;
            }));
        }
        // Size range
        if ($sizeMin || $sizeMax) {
            $docs = array_values(array_filter($docs, function ($d) use ($sizeMin, $sizeMax) {
                $sz = intval($d['file_size'] ?? 0);
                if ($sizeMin && $sz < $sizeMin) return false;
                if ($sizeMax && $sz > $sizeMax) return false;
                return true;
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


