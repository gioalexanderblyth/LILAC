<?php
// Documents API with MySQL database storage

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include universal upload handler and database config
require_once 'universal_upload_handler.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/MouSyncManager.php';

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

function updateAwardReadinessCounters($pdo) {
    // Ensure award_readiness table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS award_readiness (
        id INT AUTO_INCREMENT PRIMARY KEY,
        award_key VARCHAR(50) UNIQUE NOT NULL,
        total_documents INT DEFAULT 0,
        total_events INT DEFAULT 0,
        total_items INT DEFAULT 0,
        satisfied_criteria TEXT,
        unsatisfied_criteria TEXT,
        readiness_percentage DECIMAL(5,2) DEFAULT 0,
        is_ready BOOLEAN DEFAULT FALSE,
        last_calculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default award types if not exist
    $defaultAwards = ['leadership', 'education', 'emerging', 'regional', 'citizenship'];
    foreach ($defaultAwards as $award) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO award_readiness (award_key) VALUES (?)");
        $stmt->execute([$award]);
    }
    
    // Reset all counters
    $pdo->exec("UPDATE award_readiness SET 
        total_documents = 0, 
        total_events = 0, 
        total_items = 0,
        readiness_percentage = 0,
        is_ready = 0");
    
    // Get all documents from enhanced_documents table
    $stmt = $pdo->query("SELECT id, document_name, filename, category, extracted_content FROM enhanced_documents");
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // CHED Award Criteria (20 criteria total: 5+5+4+3+3)
    $criteria = [
        'leadership' => [
            'Champion Bold Innovation',
            'Cultivate Global Citizens', 
            'Nurture Lifelong Learning',
            'Lead with Purpose',
            'Ethical and Inclusive Leadership'
        ],
        'education' => [
            'Expand Access to Global Opportunities',
            'Foster Collaborative Innovation',
            'Embrace Inclusivity and Beyond',
            'Drive Academic Excellence',
            'Build Sustainable Partnerships'
        ],
        'emerging' => [
            'Pioneer New Frontiers',
            'Adapt and Transform',
            'Build Capacity',
            'Create Impact'
        ],
        'regional' => [
            'Comprehensive Internationalization Efforts',
            'Cooperation and Collaboration',
            'Measurable Impact'
        ],
        'citizenship' => [
            'Ignite Intercultural Understanding',
            'Empower Changemakers',
            'Cultivate Active Engagement'
        ]
    ];
    
    foreach ($docs as $doc) {
        $content = strtolower($doc['document_name'] . ' ' . $doc['filename'] . ' ' . $doc['category'] . ' ' . ($doc['extracted_content'] ?? ''));
        
        // Check each award type
        foreach ($criteria as $awardType => $awardCriteria) {
            $satisfied = [];
            foreach ($awardCriteria as $criterion) {
                if (strpos($content, strtolower($criterion)) !== false) {
                    $satisfied[] = $criterion;
                }
            }
            
            if (!empty($satisfied)) {
                // Update document counters only (not total awards)
                $stmt = $pdo->prepare("UPDATE award_readiness SET 
                    total_documents = total_documents + 1,
                    total_items = total_items + 1,
                    last_calculated = NOW()
                    WHERE award_key = ?");
                $stmt->execute([$awardType]);
            }
        }
    }
    
    // Update readiness percentages
    $stmt = $pdo->query("SELECT award_key, total_items FROM award_readiness");
    $awards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($awards as $award) {
        $threshold = 5; // Default threshold
        $readinessPercentage = min(100, ($award['total_items'] / $threshold) * 100);
        $isReady = $award['total_items'] >= $threshold;
        
        $stmt = $pdo->prepare("UPDATE award_readiness SET 
            readiness_percentage = ?,
            is_ready = ?
            WHERE award_key = ?");
        $stmt->execute([$readinessPercentage, $isReady ? 1 : 0, $award['award_key']]);
    }
}

// Ensure uploads directory exists
if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0777, true); }

// Database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Initialize MOU Sync Manager
    $mouSyncManager = new MouSyncManager($pdo);
    
    // Ensure enhanced_documents table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS enhanced_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_name VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        category VARCHAR(100) DEFAULT 'Awards',
        description TEXT,
        extracted_content LONGTEXT,
        award_assignments JSON,
        analysis_data JSON,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // REMOVED: Auto-population code that was causing files to reappear
    
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
        // Direct file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            docs_respond(false, ['message' => 'No file uploaded or upload error']);
        }
        
        $file = $_FILES['file'];
        $originalFilename = $file['name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        
        // Generate unique filename
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $uniqueFilename = 'doc_' . uniqid() . '.' . $extension;
        $uploadPath = $uploadsDir . '/' . $uniqueFilename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            docs_respond(false, ['message' => 'Failed to save file']);
        }
        
        // Get additional data from POST
        $documentName = $_POST['document_name'] ?? pathinfo($originalFilename, PATHINFO_FILENAME);
        $description = $_POST['description'] ?? '';
        $awardType = trim((string)($_POST['award_type'] ?? ''));
        
        // Check if this file already exists in the database (by filename)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enhanced_documents WHERE filename = ?");
        $stmt->execute([$uniqueFilename]);
        $exists = $stmt->fetchColumn();
        
        if ($exists > 0) {
            // Delete the uploaded file since it's a duplicate
            unlink($uploadPath);
            docs_respond(false, ['message' => 'File already exists in the database']);
        }
        
        // Determine category
        $category = 'Awards'; // Default category
        if (preg_match('/\b(MOU|MOA|MEMORANDUM OF UNDERSTANDING|AGREEMENT|KUMA-MOU)\b/i', $documentName)) {
            $category = 'MOUs & MOAs';
        } elseif (preg_match('/\b(REGISTRAR|TRANSCRIPT|TOR|CERTIFICATE|COR|GWA|GRADES|ENROLLMENT|STUDENT\s*RECORD)\b/i', $documentName)) {
            $category = 'Registrar Files';
        } elseif (preg_match('/\b(TEMPLATE|FORM|ADMISSION|APPLICATION|REGISTRATION|CHECKLIST|REQUEST)\b/i', $documentName)) {
            $category = 'Templates';
        }
        
        // Extract content from the uploaded file
        $extractedContent = '';
        $filePath = 'uploads/' . $uniqueFilename;
        if (file_exists($filePath)) {
            $extractedContent = file_get_contents($filePath);
        }
        
        // Insert into database with extracted content
        $stmt = $pdo->prepare("INSERT INTO enhanced_documents (document_name, filename, file_path, file_size, file_type, category, description, extracted_content) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $documentName,
            $uniqueFilename,
            $filePath,
            $fileSize,
            $fileType,
            $category,
            $description,
            $extractedContent
        ]);
        
        $documentId = $pdo->lastInsertId();
        
        // Sync to MOU table if category is MOU-related
        $syncResult = $mouSyncManager->syncUpload(
            $documentId,
            $documentName,
            $uniqueFilename,
            $filePath,
            $fileSize,
            $fileType,
            $category,
            $description,
            $extractedContent
        );
        
        // Update award_readiness counters after successful upload
        updateAwardReadinessCounters($pdo);
        
        // Return success response
        $record = [
            'id' => $documentId,
            'document_name' => $documentName,
            'filename' => $uniqueFilename,
            'original_filename' => $originalFilename,
            'file_path' => 'uploads/' . $uniqueFilename,
            'file_size' => $fileSize,
            'category' => $category,
            'description' => $description,
            'upload_date' => date('Y-m-d H:i:s'),
            'status' => 'Active',
            'ocr_text' => '',
            'award_type' => $awardType,
            'universal_file_id' => $documentId
        ];
        
        docs_respond(true, [
            'message' => 'Document uploaded successfully', 
            'document' => $record,
            'mou_sync' => $syncResult
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

    // Delete document
    if ($action === 'delete') {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            docs_respond(false, ['message' => 'Document ID required']);
        }
        
        // First, get the file information before deleting
        $stmt = $pdo->prepare("SELECT document_name, filename, file_path, category FROM enhanced_documents WHERE id = ?");
        $stmt->execute([$id]);
        $fileInfo = $stmt->fetch();
        
        if (!$fileInfo) {
            docs_respond(false, ['message' => 'Document not found']);
        }
        
        // Sync deletion to MOU table if category is MOU-related
        $syncResult = $mouSyncManager->syncDeletion(
            $id,
            $fileInfo['document_name'],
            $fileInfo['filename']
        );
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM enhanced_documents WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete the actual file from the file system
        $filePath = dirname(__DIR__) . '/' . $fileInfo['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        docs_respond(true, [
            'message' => 'Document permanently deleted',
            'mou_sync' => $syncResult
        ]);
    }

    // Get categories
    if ($action === 'get_categories') {
        $sql = "SELECT DISTINCT category FROM enhanced_documents";
        $stmt = $pdo->query($sql);
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        docs_respond(true, ['categories' => $categories]);
    }

    // Get stats
    if ($action === 'get_stats') {
        $sql = "SELECT COUNT(*) as total, SUM(file_size) as total_size FROM enhanced_documents";
        $stmt = $pdo->query($sql);
        $stats = $stmt->fetch();
        
        // Get recent uploads (last 30 days)
        $recentSql = "SELECT COUNT(*) FROM enhanced_documents WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $recentStmt = $pdo->query($recentSql);
        $recentCount = $recentStmt->fetchColumn();
        
        // Get this month's uploads
        $monthSql = "SELECT COUNT(*) FROM enhanced_documents WHERE upload_date >= DATE_FORMAT(NOW(), '%Y-%m-01')";
        $monthStmt = $pdo->query($monthSql);
        $monthCount = $monthStmt->fetchColumn();
        
        docs_respond(true, [
            'stats' => [
                'total' => intval($stats['total']),
                'recent' => intval($recentCount),
                'total_size' => intval($stats['total_size']),
                'month_uploads' => intval($monthCount)
            ]
        ]);
    }

    // get_all with pagination, search, category, sorting
    if ($action === 'get_all' || $action === 'get_by_category') {
        error_log("Processing get_all action");
        
        // Add debugging
        error_log("Database connection successful");
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
        $search = trim((string)($_GET['search'] ?? ''));
        $category = ($action === 'get_by_category') ? (string)($_GET['category'] ?? '') : (string)($_GET['category'] ?? '');
        $sortBy = $_GET['sort_by'] ?? 'upload_date';
        $sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');

        // Build SQL query - use enhanced_documents table from lilac_system database
        $sql = "SELECT * FROM enhanced_documents WHERE 1=1";
        $params = [];
        
        // Add category filter - use category field from enhanced_documents table
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
            error_log("Executing SQL: " . $sql);
            error_log("Parameters: " . json_encode($params));
            
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($docs) . " documents");

            // Get total count for pagination
            $countSql = "SELECT COUNT(*) FROM enhanced_documents WHERE 1=1";
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
                $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
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
                    'file_path' => $doc['file_path'], // Use the stored file_path
                    'file_size' => intval($doc['file_size'] ?? 0),
                    'category' => $doc['category'],
                    'description' => $doc['description'] ?? '',
                    'upload_date' => $doc['upload_date'],
                    'status' => 'Active',
                    'ocr_text' => $doc['extracted_content'] ?? '',
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


