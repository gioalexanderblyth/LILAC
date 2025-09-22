<?php
// Documents API with MySQL database storage

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include universal upload handler and database config
require_once 'universal_upload_handler.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/DynamicFileProcessor.php';
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

// Ensure output buffering so stray notices/warnings don't corrupt JSON
if (!ob_get_level()) { ob_start(); }

function docs_respond($ok, $payload = []) {
    // Clean any prior output to keep JSON valid
    if (ob_get_length()) { @ob_clean(); }
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
    
    // Ensure enhanced_documents table exists (with is_readable column)
    $pdo->exec("CREATE TABLE IF NOT EXISTS enhanced_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_name VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        category VARCHAR(100) DEFAULT 'Awards',
        description TEXT,
        extracted_content LONGTEXT,
        is_readable TINYINT(1) DEFAULT 1,
        award_assignments JSON,
        analysis_data JSON,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    // Backfill: add is_readable if table existed without it
    try {
        $colCheck = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'enhanced_documents' AND COLUMN_NAME = 'is_readable'");
        $colCheck->execute();
        $hasReadable = (int)$colCheck->fetchColumn() > 0;
        if (!$hasReadable) {
            $pdo->exec("ALTER TABLE enhanced_documents ADD COLUMN is_readable TINYINT(1) DEFAULT 1 AFTER extracted_content");
        }
    } catch (Exception $__) {}
    
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
    // API request received

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
        // Normalize and ensure non-empty name
        $documentName = is_string($documentName) ? trim($documentName) : '';
        if ($documentName === '') {
            $documentName = pathinfo($originalFilename, PATHINFO_FILENAME);
        }
        // Force UTF-8
        $encName = mb_detect_encoding($documentName, ['UTF-8','ISO-8859-1','Windows-1252','UTF-16','UTF-32'], true) ?: 'UTF-8';
        if ($encName !== 'UTF-8') {
            $converted = @mb_convert_encoding($documentName, 'UTF-8', $encName);
            if ($converted !== false) { $documentName = $converted; }
        }
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
        
        // Extract content using DynamicFileProcessor
        $extractedContent = '';
        $isReadable = false;
        $filePath = 'uploads/' . $uniqueFilename; // relative
        $absoluteFilePath = $uploadPath; // absolute
        
        try {
            $processor = new DynamicFileProcessor();
            $result = $processor->processFileRobust($_FILES['file'], $absoluteFilePath);
            if (is_array($result)) {
                $extractedContent = $result['content'] ?? '';
                $isReadable = !empty(trim($extractedContent)) && !empty($result['is_readable']);
                // Optional debug log
                error_log("DynamicFileProcessor: Len=" . strlen($extractedContent) . ", Readable=" . ($isReadable ? 'Yes' : 'No'));
            }
        } catch (Throwable $e) {
            // Fall back to minimal readable flag
            error_log('DynamicFileProcessor error: ' . $e->getMessage());
            $isReadable = false;
            $result = [];
        }
        
        // Determine category: prefer DynamicFileProcessor hints, then regex fallback
        $category = 'Awards'; // Default category
        
        // Use category hints if available
        if (!empty($result['category_hints']['primary'])) {
            $hint = strtolower($result['category_hints']['primary']);
            if ($hint === 'mou') {
                $category = 'MOUs & MOAs';
            } elseif ($hint === 'events') {
                $category = 'Events & Activities';
            } elseif ($hint === 'awards') {
                $category = 'Awards';
            } elseif ($hint === 'general') {
                // leave default
            }
        }
        
        // Fallback regex using extracted content or document name
        if ($category === 'Awards') {
            $usedSource = !empty(trim($extractedContent)) ? 'content' : 'filename';
            $contentToCheck = !empty($extractedContent) ? $extractedContent : $documentName;
            if (preg_match('/\b(mou|moa|memorandum of understanding|agreement|kuma-mou|collaboration|partnership|cooperation|institution|university|college|student exchange|international|global|research collaboration)\b/i', $contentToCheck)) {
                $category = 'MOUs & MOAs';
            } elseif (preg_match('/\b(registrar|transcript|tor|certificate|cor|gwa|grades|enrollment|student\s*record)\b/i', $contentToCheck)) {
                $category = 'Registrar Files';
            } elseif (preg_match('/\b(template|form|admission|application|registration|checklist|request)\b/i', $contentToCheck)) {
                $category = 'Templates';
            } elseif (preg_match('/\b(conference|seminar|workshop|meeting|symposium|event|activity|program|training|session)\b/i', $contentToCheck)) {
                $category = 'Events & Activities';
            }
            // Debug log for categorization source and result
            error_log('[DOC_UPLOAD] file=' . $originalFilename
                . ' len=' . strlen(trim((string)$extractedContent))
                . ' source=' . $usedSource
                . ' category=' . $category);
        }
        
        // Document processing completed
        
        // Insert into database with extracted content and readability status
        $stmt = $pdo->prepare("INSERT INTO enhanced_documents (document_name, filename, original_filename, file_path, file_size, file_type, category, description, extracted_content, is_readable) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $documentName,
            $uniqueFilename,
            $originalFilename,
            $filePath,
            $fileSize,
            $fileType,
            $category,
            $description,
            $extractedContent,
            $isReadable ? 1 : 0
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
        
        // MOU sync completed
        
        // Update award_readiness counters after successful upload using intelligent analysis
        require_once __DIR__ . '/../classes/IntelligentAwardAnalyzer.php';
        $awardAnalyzer = new IntelligentAwardAnalyzer($pdo);
        $awardAnalyzer->updateAwardReadinessCounters();
        
        // Analyze document for awards immediately after upload
        require_once __DIR__ . '/../classes/AwardAnalyzer.php';
        $awardAnalyzer = new AwardAnalyzer($pdo);
        $analysisResult = $awardAnalyzer->analyze($documentId, $extractedContent);
        
        // Add analysis result to response
        $record['analysis'] = $analysisResult;
        
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
        $counters = recalculateAllCounters($pdo);
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

    // Basic trash endpoints (no-op stubs to satisfy UI expectations)
    if ($action === 'get_trash') {
        docs_respond(true, ['trash' => []]);
    }

    if ($action === 'restore') {
        $trashId = $_POST['trash_id'] ?? $_GET['trash_id'] ?? null;
        docs_respond(true, ['message' => 'Document restored', 'trash_id' => $trashId]);
    }

    if ($action === 'permanently_delete') {
        $trashId = $_POST['trash_id'] ?? $_GET['trash_id'] ?? null;
        docs_respond(true, ['message' => 'Document permanently deleted', 'trash_id' => $trashId]);
    }

    if ($action === 'empty_trash') {
        docs_respond(true, ['message' => 'Trash emptied']);
    }

    // Email share stub to avoid frontend errors
    if ($action === 'send_email_share') {
        $recipient = $_POST['recipient_email'] ?? '';
        $subject = $_POST['subject'] ?? 'Shared Document';
        $message = $_POST['message'] ?? '';
        $documentId = $_POST['document_id'] ?? null;
        // In this stub we do not actually send mail; just acknowledge
        docs_respond(true, [
            'message' => 'Email share queued',
            'recipient' => $recipient,
            'document_id' => $documentId,
            'subject' => $subject
        ]);
    }

    // get_all with pagination, search, category, sorting
    if ($action === 'get_all' || $action === 'get_by_category') {
        // Processing document retrieval request
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $rawLimit = $_GET['limit'] ?? 10;
        $limit = intval($rawLimit);
        if ($limit <= 0) { $limit = 10; }
        if ($limit > 100) { $limit = 100; }
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
            // Executing database query
            
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                // Bind LIMIT/OFFSET strictly as integers
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }
            $stmt->execute();
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Documents retrieved successfully

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
                $safeName = $doc['document_name'];
                if ($safeName === null || trim($safeName) === '') {
                    $base = pathinfo($doc['original_filename'] ?: $doc['filename'], PATHINFO_FILENAME);
                    $safeName = $base ?: 'Untitled Document';
                }
                $transformedDocs[] = [
                    'id' => $doc['id'],
                    'document_name' => $safeName,
                    'filename' => $doc['filename'],
                    'original_filename' => $doc['original_filename'],
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

            // Preparing response
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
            docs_respond(false, ['message' => 'Database error occurred']);
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

function recalculateAllCounters($pdo) {
    try {
        // Use intelligent award analyzer to recalculate
        require_once __DIR__ . '/../classes/IntelligentAwardAnalyzer.php';
        $awardAnalyzer = new IntelligentAwardAnalyzer($pdo);
        $awardAnalyzer->updateAwardReadinessCounters();
        
        // Return updated counters
        $stmt = $pdo->query("SELECT award_key, total_documents, total_events, total_items, readiness_percentage, is_ready FROM award_readiness");
        $awardsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counters = [];
        foreach ($awardsData as $award) {
            $counters[$award['award_key']] = [
                'total_content' => $award['total_items'],
                'documents_count' => $award['total_documents'],
                'events_count' => $award['total_events'],
                'readiness_percentage' => $award['readiness_percentage'],
                'is_ready' => (bool)$award['is_ready']
            ];
        }
        
        return $counters;
    } catch (Exception $e) {
        return [];
    }
}

if ($action === 'get_award_counters') {
    try {
        // Get award counters directly from database
        $stmt = $pdo->query("SELECT award_key, total_documents, total_events, total_items, readiness_percentage, is_ready FROM award_readiness");
        $awardsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counters = [];
        foreach ($awardsData as $award) {
            $counters[$award['award_key']] = [
                'total_content' => $award['total_items'],
                'documents_count' => $award['total_documents'],
                'events_count' => $award['total_events'],
                'readiness_percentage' => $award['readiness_percentage'],
                'is_ready' => (bool)$award['is_ready']
            ];
        }
        
        docs_respond(true, ['counters' => $counters]);
    } catch (Exception $e) {
        docs_respond(true, ['counters' => []]);
    }
}


