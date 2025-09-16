<?php
// Documents API with MySQL database storage

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include universal upload handler and database config
require_once 'universal_upload_handler.php';
require_once '../config/database.php';

// Paths
$rootDir = dirname(__DIR__);
$uploadsDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function docs_respond($ok, $payload = []) {
    echo json_encode(array_merge(['success' => $ok], $payload));
    exit;
}

// Ensure uploads directory exists
if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0777, true); }

// Database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    docs_respond(false, ['message' => 'Database connection failed: ' . $e->getMessage()]);
}

function sanitize_filename($name) {
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    return trim($name, '_');
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_all');

try {
    error_log("API called with action: " . ($_GET['action'] ?? 'none'));

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
        // Use universal upload handler
        $uploadHandler = new UniversalUploadHandler();
        $uploadResult = $uploadHandler->handleUpload($_FILES['file'], 'system', 'docs');
        
        if (!$uploadResult['success']) {
            docs_respond(false, ['message' => $uploadResult['error']]);
        }
        
        // Get additional data from POST
        $documentName = $_POST['document_name'] ?? ($_POST['title'] ?? pathinfo($_FILES['file']['name'], PATHINFO_FILENAME));
        $description = $_POST['description'] ?? '';
        $awardType = trim((string)($_POST['award_type'] ?? ''));
        
        // Use the category determined by universal upload handler
        $category = $uploadResult['category'];
        $categoryConfidence = 0.8; // High confidence for universal handler categorization

        // Auto-analyze for award classification if not provided
        if (empty($awardType)) {
            $awardType = performAutoAwardAnalysis($documentName, $description, $uploadResult['extracted_text']);
        }

        // The UniversalUploadHandler already saves to MySQL database
        // We just need to return the result
        $record = [
            'id' => $uploadResult['file_id'],
            'document_name' => htmlspecialchars($documentName, ENT_QUOTES, 'UTF-8'),
            'filename' => basename($uploadResult['file_path']), // Extract filename from path
            'original_filename' => $_FILES['file']['name'], // Use original uploaded filename
            'file_path' => $uploadResult['file_path'],
            'file_size' => intval($_FILES['file']['size']),
            'category' => htmlspecialchars($category, ENT_QUOTES, 'UTF-8'),
            'category_confidence' => $categoryConfidence,
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'upload_date' => date('Y-m-d H:i:s'),
            'status' => 'Active',
            'ocr_text' => $uploadResult['extracted_text'] ?? '',
            'award_type' => htmlspecialchars($awardType, ENT_QUOTES, 'UTF-8'),
            'universal_file_id' => $uploadResult['file_id'],
            'linked_pages' => $uploadResult['linked_pages'] ?? []
        ];
        
        // Auto-analyze the uploaded document for award criteria
        $content = $documentName . ' ' . $filename . ' ' . $category . ' ' . ($description ?? '');
        $contentType = ($category === 'MOUs & MOAs') ? 'mou' : 'document';
        
        // Call auto-analysis API
        $analysisData = [
            'action' => 'auto_analyze_upload',
            'content' => $content,
            'content_type' => $contentType
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/LILAC/api/checklist.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($analysisData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
        $analysisResponse = curl_exec($ch);
        curl_close($ch);
        
        docs_respond(true, [
            'message' => 'Document added', 
            'document' => $record,
            'auto_analysis' => $analysisResponse ? json_decode($analysisResponse, true) : null
        ]);
    }

    if ($action === 'get_award_counters') {
        $counters = getAwardCounters();
        docs_respond(true, ['counters' => $counters]);
    }

    if ($action === 'recalculate_counters') {
        $counters = recalculateAllCounters();
        docs_respond(true, ['message' => 'Counters recalculated', 'counters' => $counters]);
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if (empty($id)) { docs_respond(false, ['message' => 'Invalid id']); }
        
        try {
            // Delete from documents table
            $sql = "DELETE FROM unified_documents WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                docs_respond(true, ['message' => 'Document deleted']);
            } else {
                docs_respond(false, ['message' => 'Document not found']);
            }
        } catch (PDOException $e) {
            error_log("Database error in delete: " . $e->getMessage());
            docs_respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    if ($action === 'get_categories') {
        try {
            $sql = "SELECT DISTINCT category FROM unified_documents";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            docs_respond(true, ['categories' => $categories]);
        } catch (Exception $e) {
            error_log("Error in get_categories action: " . $e->getMessage());
            docs_respond(false, ['message' => 'Error loading categories: ' . $e->getMessage()]);
        }
    }

    if ($action === 'get_stats' || $action === 'get_stats_by_category') {
        try {
            $category = $_GET['category'] ?? '';
            $sql = "SELECT COUNT(*) as total, SUM(file_size) as total_size FROM unified_documents";
            $params = [];
            
            if ($action === 'get_stats_by_category' && $category !== '') {
                $sql .= " WHERE category = :category";
                $params[':category'] = $category;
            }
            
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get recent counts
            $recentSql = "SELECT COUNT(*) FROM unified_documents WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $monthSql = "SELECT COUNT(*) FROM unified_documents WHERE upload_date >= DATE_FORMAT(NOW(), '%Y-%m-01')";
            
            if ($action === 'get_stats_by_category' && $category !== '') {
                $recentSql .= " AND category = :category";
                $monthSql .= " AND category = :category";
            }
            
            $recentStmt = $pdo->prepare($recentSql);
            $monthStmt = $pdo->prepare($monthSql);
            
            foreach ($params as $key => $value) {
                $recentStmt->bindValue($key, $value);
                $monthStmt->bindValue($key, $value);
            }
            
            $recentStmt->execute();
            $monthStmt->execute();
            
            $recentCount = $recentStmt->fetchColumn();
            $monthCount = $monthStmt->fetchColumn();
            
            docs_respond(true, ['stats' => [
                'total_documents' => intval($stats['total']),
                'total' => intval($stats['total']),
                'recent' => intval($recentCount),
                'month' => intval($monthCount),
                'total_size' => intval($stats['total_size'] ?? 0),
            ]]);
        } catch (Exception $e) {
            error_log("Error in get_stats action: " . $e->getMessage());
            docs_respond(false, ['message' => 'Error loading stats: ' . $e->getMessage()]);
        }
    }

    if ($action === 'send_email_share') {
        // Minimal stub: log to email_log.txt
        $logFile = $rootDir . DIRECTORY_SEPARATOR . 'email_log.txt';
        $to = $_POST['email'] ?? '';
        $docs = $_POST['documents'] ?? '[]';
        $entry = date('c') . " | To: {$to} | Docs: {$docs}\n";
        file_put_contents($logFile, $entry, FILE_APPEND);
        docs_respond(true, ['message' => 'Share logged']);
    }

    // get_all with pagination, search, category, sorting
    if ($action === 'get_all' || $action === 'get_by_category') {
        error_log("Processing get_all action");
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
        $search = trim((string)($_GET['search'] ?? ''));
        $category = ($action === 'get_by_category') ? (string)($_GET['category'] ?? '') : (string)($_GET['category'] ?? '');
        $sortBy = $_GET['sort_by'] ?? 'upload_date';
        $sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');

        // Build SQL query - use documents table from lilac_system database
        $sql = "SELECT * FROM unified_documents WHERE 1=1";
        $params = [];
        
        // Add category filter - use category field from documents table
        if ($category !== '') {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }
        
        // Add search filter
        if ($search !== '') {
            $sql .= " AND (filename LIKE :search OR document_name LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        // Add sorting
        $allowedSortFields = ['upload_date', 'filename', 'document_name', 'file_size'];
        if (in_array($sortBy, $allowedSortFields)) {
            $sql .= " ORDER BY " . $sortBy . " " . $sortOrder;
        } else {
            $sql .= " ORDER BY upload_date DESC";
        }
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        try {
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) FROM unified_documents WHERE 1=1";
            $countParams = [];
            if ($category !== '') {
                $countSql .= " AND category = :category";
                $countParams[':category'] = $category;
            }
            if ($search !== '') {
                $countSql .= " AND (filename LIKE :search OR document_name LIKE :search OR description LIKE :search)";
                $countParams[':search'] = '%' . $search . '%';
            }
            
            $countStmt = $pdo->prepare($countSql);
            foreach ($countParams as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetchColumn();

            // Transform data to match expected format
            $transformedDocs = [];
            foreach ($docs as $doc) {
                $transformedDocs[] = [
                    'id' => $doc['id'],
                    'document_name' => $doc['document_name'],
                    'filename' => $doc['filename'],
                    'original_filename' => $doc['filename'], // Use filename as original_filename
                    'file_path' => 'uploads/' . $doc['filename'], // Construct file path
                    'file_size' => intval($doc['file_size'] ?? 0),
                    'category' => $doc['category'],
                    'description' => $doc['description'] ?? '',
                    'upload_date' => $doc['upload_date'],
                    'status' => 'Active',
                    'ocr_text' => '',
                    'award_type' => '',
                    'universal_file_id' => $doc['id']
                ];
            }

            error_log("Responding with " . count($transformedDocs) . " documents");
            docs_respond(true, [
                'documents' => $transformedDocs,
                'pagination' => [
                    'total_documents' => $total,
                    'current_page' => $page,
                    'limit' => $limit,
                    'total_pages' => (int)ceil($total / $limit)
                ]
            ]);
        } catch (PDOException $e) {
            error_log("Database error in get_all: " . $e->getMessage());
            docs_respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
        }
    }


    docs_respond(false, ['message' => 'Unknown action']);
} catch (Throwable $e) {
    docs_respond(false, ['message' => 'Server error', 'error' => $e->getMessage()]);
}


// Simple award analysis function (simplified for MySQL version)
function performAutoAwardAnalysis($documentName, $description, $ocrText) {
    // For now, return empty string since award system is not fully implemented in MySQL version
    return '';
}

// Simplified counter functions (return empty for now)
function getAwardCounters() {
    return [
        'leadership' => ['documents' => 0, 'events' => 0, 'threshold' => 5, 'readiness' => 'Incomplete', 'total_content' => 0],
        'education' => ['documents' => 0, 'events' => 0, 'threshold' => 3, 'readiness' => 'Incomplete', 'total_content' => 0],
        'emerging' => ['documents' => 0, 'events' => 0, 'threshold' => 3, 'readiness' => 'Incomplete', 'total_content' => 0],
        'regional' => ['documents' => 0, 'events' => 0, 'threshold' => 4, 'readiness' => 'Incomplete', 'total_content' => 0],
        'global' => ['documents' => 0, 'events' => 0, 'threshold' => 3, 'readiness' => 'Incomplete', 'total_content' => 0]
    ];
}

function recalculateAllCounters() {
    return getAwardCounters();
}

if ($action === 'get_award_counters') {
    try {
        // Get award counters from our new awards API
        $awardsResponse = file_get_contents('http://localhost/LILAC/api/awards.php?action=get_counts');
        $awardsData = json_decode($awardsResponse, true);
        
        if ($awardsData && $awardsData['success']) {
            $counters = [];
            foreach ($awardsData['counts'] as $awardKey => $awardData) {
                $counters[$awardKey] = [
                    'total_content' => $awardData['count'],
                    'documents_count' => $awardData['count'],
                    'events_count' => 0, // We can add events counting later
                    'mous_count' => 0    // We can add MOU counting later
                ];
            }
            
            docs_respond(true, ['counters' => $counters]);
        } else {
            docs_respond(true, ['counters' => []]);
        }
    } catch (Exception $e) {
        error_log("Error getting award counters: " . $e->getMessage());
        docs_respond(true, ['counters' => []]);
    }
}


