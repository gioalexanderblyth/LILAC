<?php
// Lightweight Documents API with JSON storage and file uploads

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    if ($raw === false) {
        error_log("Failed to read documents.json file");
        return ['auto_id' => 1, 'documents' => [], 'trash' => []];
    }
    
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in documents.json: " . json_last_error_msg());
        return ['auto_id' => 1, 'documents' => [], 'trash' => []];
    }
    
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
    error_log("API called with action: " . ($_GET['action'] ?? 'none'));
    $db = load_db($dbFile);
    error_log("Database loaded successfully, documents count: " . count($db['documents']));

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
        $awardType = trim((string)($_POST['award_type'] ?? ''));
        
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

        // Auto-analyze for award classification if not provided
        if (empty($awardType)) {
            $awardType = performAutoAwardAnalysis($documentName, $description, $ocrText);
        }

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
            'ocr_text' => $ocrText,
            'award_type' => htmlspecialchars($awardType, ENT_QUOTES, 'UTF-8')
        ];
        $db['documents'][] = $record;
        save_db($dbFile, $db);
        
        // Auto-update counters and checklist if award type was determined
        if (!empty($awardType)) {
            updateAwardCounters($awardType, 'document');
            autoUpdateChecklistForDocument($record);
        }
        
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

    if ($action === 'get_award_counters') {
        $counters = getAwardCounters();
        respond(true, ['counters' => $counters]);
    }

    if ($action === 'recalculate_counters') {
        $counters = recalculateAllCounters();
        respond(true, ['message' => 'Counters recalculated', 'counters' => $counters]);
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
        try {
            $cats = [];
            foreach ($db['documents'] as $doc) {
                if (!empty($doc['category'])) { $cats[$doc['category']] = true; }
            }
            respond(true, ['categories' => array_values(array_keys($cats))]);
        } catch (Exception $e) {
            error_log("Error in get_categories action: " . $e->getMessage());
            respond(false, ['message' => 'Error loading categories: ' . $e->getMessage()]);
        }
    }

    if ($action === 'get_stats' || $action === 'get_stats_by_category') {
        try {
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
        } catch (Exception $e) {
            error_log("Error in get_stats action: " . $e->getMessage());
            respond(false, ['message' => 'Error loading stats: ' . $e->getMessage()]);
        }
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
        error_log("Processing get_all action");
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

        error_log("Responding with " . count($paged) . " documents");
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

    if ($action === 'get_by_award') {
        $awardType = $_GET['award_type'] ?? '';
        if (empty($awardType)) {
            respond(false, ['message' => 'Award type required']);
        }
        
        $filtered = array_filter($db['documents'], function($doc) use ($awardType) {
            return $doc['status'] === 'Active' && $doc['award_type'] === $awardType;
        });
        
        respond(true, ['documents' => array_values($filtered)]);
    }

    if ($action === 'get_award_counts') {
        $awardTypes = [
            'leadership' => 'Internationalization (IZN) Leadership Award',
            'education' => 'Outstanding International Education Program Award', 
            'emerging' => 'Emerging Leadership Award',
            'regional' => 'Best Regional Office for Internationalization Award',
            'citizenship' => 'Global Citizenship Award'
        ];
        
        $counts = [];
        foreach ($awardTypes as $key => $awardName) {
            $counts[$key] = count(array_filter($db['documents'], function($doc) use ($awardName) {
                return $doc['status'] === 'Active' && $doc['award_type'] === $awardName;
            }));
        }
        
        respond(true, ['counts' => $counts]);
    }

    if ($action === 'analyze_document') {
        $documentId = $_POST['document_id'] ?? '';
        if (empty($documentId)) {
            respond(false, ['message' => 'Document ID required']);
        }
        
        // Find the document
        $document = null;
        foreach ($db['documents'] as $doc) {
            if ($doc['id'] == $documentId && $doc['status'] === 'Active') {
                $document = $doc;
                break;
            }
        }
        
        if (!$document) {
            respond(false, ['message' => 'Document not found']);
        }
        
        // Perform analysis (this would typically call the DocumentAnalyzer)
        $analysis = performDocumentAnalysis($document);
        
        respond(true, ['analysis' => $analysis]);
    }

    if ($action === 'get_award_analysis') {
        $awardType = $_GET['award_type'] ?? '';
        if (empty($awardType)) {
            respond(false, ['message' => 'Award type required']);
        }
        
        // Get documents for this award
        $documents = array_filter($db['documents'], function($doc) use ($awardType) {
            return $doc['status'] === 'Active' && $doc['award_type'] === $awardType;
        });
        
        // Analyze criteria satisfaction
        $criteria = getAwardCriteria($awardType);
        $satisfiedCriteria = [];
        $unsatisfiedCriteria = [];
        
        foreach ($criteria as $criterion) {
            $isSatisfied = false;
            foreach ($documents as $doc) {
                if (checkCriterionSatisfaction($doc, $criterion)) {
                    $isSatisfied = true;
                    break;
                }
            }
            
            if ($isSatisfied) {
                $satisfiedCriteria[] = $criterion;
            } else {
                $unsatisfiedCriteria[] = $criterion;
            }
        }
        
        respond(true, [
            'documents' => array_values($documents),
            'document_count' => count($documents),
            'satisfied_criteria' => $satisfiedCriteria,
            'unsatisfied_criteria' => $unsatisfiedCriteria,
            'satisfaction_rate' => count($satisfiedCriteria) / count($criteria)
        ]);
    }

    respond(false, ['message' => 'Unknown action']);
} catch (Throwable $e) {
    respond(false, ['message' => 'Server error', 'error' => $e->getMessage()]);
}

/**
 * Helper function to get award criteria
 */
function getAwardCriteria($awardType) {
    $criteria = [
        'Internationalization (IZN) Leadership Award' => [
            'Champion Bold Innovation',
            'Cultivate Global Citizens', 
            'Nurture Lifelong Learning',
            'Lead with Purpose',
            'Ethical and Inclusive Leadership'
        ],
        'Outstanding International Education Program Award' => [
            'Expand Access to Global Opportunities',
            'Foster Collaborative Innovation',
            'Embrace Inclusivity and Beyond'
        ],
        'Emerging Leadership Award' => [
            'Innovation',
            'Strategic and Inclusive Growth',
            'Empowerment of Others'
        ],
        'Best Regional Office for Internationalization Award' => [
            'Comprehensive Internationalization Efforts',
            'Cooperation and Collaboration',
            'Measurable Impact'
        ],
        'Global Citizenship Award' => [
            'Ignite Intercultural Understanding',
            'Empower Changemakers',
            'Cultivate Active Engagement'
        ]
    ];
    
    return $criteria[$awardType] ?? [];
}

/**
 * Helper function to check if a document satisfies a criterion
 */
function checkCriterionSatisfaction($document, $criterion) {
    $text = strtolower($document['document_name'] . ' ' . $document['description'] . ' ' . ($document['ocr_text'] ?? ''));
    $criterionLower = strtolower($criterion);
    
    // Simple keyword matching - in production, this would be more sophisticated
    $keywords = explode(' ', $criterionLower);
    $matchedKeywords = 0;
    
    foreach ($keywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            $matchedKeywords++;
        }
    }
    
    // Consider criterion satisfied if at least 50% of keywords match
    return $matchedKeywords >= (count($keywords) * 0.5);
}

/**
 * Helper function to perform document analysis
 */
function performDocumentAnalysis($document) {
    $text = strtolower($document['document_name'] . ' ' . $document['description'] . ' ' . ($document['ocr_text'] ?? ''));
    
    // Simple analysis - in production, this would use the DocumentAnalyzer class
    $awardTypes = [
        'Internationalization (IZN) Leadership Award' => ['leadership', 'international', 'global', 'innovation'],
        'Outstanding International Education Program Award' => ['education', 'program', 'academic', 'learning'],
        'Emerging Leadership Award' => ['emerging', 'innovative', 'new', 'development'],
        'Best Regional Office for Internationalization Award' => ['regional', 'office', 'cooperation', 'collaboration'],
        'Global Citizenship Award' => ['citizenship', 'community', 'cultural', 'engagement']
    ];
    
    $scores = [];
    foreach ($awardTypes as $award => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            $score += substr_count($text, $keyword);
        }
        $scores[$award] = $score;
    }
    
    $bestMatch = array_keys($scores, max($scores))[0];
    $confidence = max($scores) > 0 ? min(max($scores) / 5, 1.0) : 0;
    
    return [
        'best_match' => $bestMatch,
        'confidence' => $confidence,
        'scores' => $scores
    ];
}

// Auto-analyze document for award classification
function performAutoAwardAnalysis($documentName, $description, $ocrText) {
    $content = $documentName . ' ' . $description . ' ' . $ocrText;
    $contentLower = strtolower($content);
    
    // Award keywords mapping
    $awardKeywords = [
        'leadership' => [
            'keywords' => ['leadership', 'internationalization', 'global', 'strategic', 'vision', 'innovation', 'partnership', 'collaboration', 'exchange', 'program', 'initiative', 'development', 'international', 'cross-cultural', 'cultural', 'diversity', 'inclusion', 'mentorship', 'faculty', 'student', 'research', 'academic', 'institutional', 'governance', 'policy', 'framework', 'strategy', 'planning', 'management', 'administration', 'award', 'recognition', 'excellence', 'achievement', 'impact', 'outcome', 'champion', 'bold', 'innovation', 'cultivate', 'global citizens', 'lifelong learning', 'purpose', 'ethical', 'inclusive leadership']
        ],
        'education' => [
            'keywords' => ['education', 'program', 'curriculum', 'academic', 'course', 'learning', 'teaching', 'pedagogy', 'instruction', 'training', 'development', 'skill', 'knowledge', 'expertise', 'competency', 'qualification', 'certification', 'degree', 'diploma', 'certificate', 'study', 'research', 'scholarship', 'international', 'global', 'cross-cultural', 'multicultural', 'diverse', 'inclusive', 'accessible', 'opportunity', 'access', 'expand', 'foster', 'collaborative', 'innovation', 'beyond', 'inclusivity']
        ],
        'emerging' => [
            'keywords' => ['emerging', 'leadership', 'innovation', 'growth', 'development', 'strategic', 'inclusive', 'empowerment', 'mentoring', 'guidance', 'support', 'advancement', 'progress', 'future', 'potential', 'talent', 'young', 'new', 'rising', 'upcoming', 'promising', 'breakthrough', 'pioneering', 'cutting-edge', 'forward-thinking', 'visionary', 'transformative', 'revolutionary', 'groundbreaking']
        ],
        'regional' => [
            'keywords' => ['regional', 'office', 'internationalization', 'comprehensive', 'efforts', 'cooperation', 'collaboration', 'measurable', 'impact', 'regional', 'local', 'community', 'partnership', 'network', 'coordination', 'integration', 'unified', 'systematic', 'organized', 'structured', 'planned', 'strategic', 'outreach', 'engagement', 'involvement', 'participation', 'contribution', 'service', 'support']
        ],
        'global' => [
            'keywords' => ['global', 'citizenship', 'intercultural', 'understanding', 'changemakers', 'engagement', 'active', 'community', 'social', 'responsibility', 'awareness', 'consciousness', 'empathy', 'tolerance', 'respect', 'diversity', 'inclusion', 'equity', 'justice', 'sustainability', 'environmental', 'humanitarian', 'volunteer', 'service', 'advocacy', 'activism', 'leadership', 'initiative', 'movement', 'change', 'transformation']
        ]
    ];
    
    $awardScores = [];
    
    // Calculate scores for each award
    foreach ($awardKeywords as $awardType => $awardData) {
        $score = 0;
        foreach ($awardData['keywords'] as $keyword) {
            if (strpos($contentLower, strtolower($keyword)) !== false) {
                $score += 1;
            }
        }
        $awardScores[$awardType] = $score;
    }
    
    // Return the award with the highest score, or empty if no matches
    if (max($awardScores) > 0) {
        return array_keys($awardScores, max($awardScores))[0];
    }
    
    return '';
}

// Auto-update checklist for uploaded document (optional counter-based)
function autoUpdateChecklistForDocument($document) {
    $awardType = $document['award_type'];
    if (empty($awardType)) {
        return;
    }
    
    // Load checklist database
    $checklistDbFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'checklist.json';
    if (!file_exists($checklistDbFile)) {
        return;
    }
    
    $checklistDb = json_decode(file_get_contents($checklistDbFile), true);
    if (!is_array($checklistDb)) {
        $checklistDb = ['criterion_links' => [], 'counter_based' => true];
    }
    
    if (!isset($checklistDb['criterion_links'])) {
        $checklistDb['criterion_links'] = [];
    }
    
    // If counter-based mode is enabled, mark all criteria as satisfied if any content exists
    if (isset($checklistDb['counter_based']) && $checklistDb['counter_based']) {
        $criteria = getAwardCriteria($awardType);
        
        foreach ($criteria as $criterion) {
            $linkKey = $awardType . '_' . md5($criterion);
            $linkId = 'document_' . $document['id'];
            
            if (!isset($checklistDb['criterion_links'][$linkKey])) {
                $checklistDb['criterion_links'][$linkKey] = [];
            }
            
            $checklistDb['criterion_links'][$linkKey][$linkId] = [
                'award_type' => $awardType,
                'criterion' => $criterion,
                'content_id' => $document['id'],
                'content_type' => 'document',
                'linked_at' => date('Y-m-d H:i:s'),
                'confidence' => 100,
                'auto_linked' => true,
                'counter_based' => true
            ];
        }
    } else {
        // Original detailed analysis approach
        $content = $document['document_name'] . ' ' . $document['description'] . ' ' . $document['ocr_text'];
        $analysis = performContentAnalysis($content);
        
        // Link document to satisfied criteria
        foreach ($analysis['satisfied_criteria'] as $criterionData) {
            if ($criterionData['award_type'] === $awardType) {
                $linkKey = $awardType . '_' . md5($criterionData['criterion']);
                $linkId = 'document_' . $document['id'];
                
                if (!isset($checklistDb['criterion_links'][$linkKey])) {
                    $checklistDb['criterion_links'][$linkKey] = [];
                }
                
                $checklistDb['criterion_links'][$linkKey][$linkId] = [
                    'award_type' => $awardType,
                    'criterion' => $criterionData['criterion'],
                    'content_id' => $document['id'],
                    'content_type' => 'document',
                    'linked_at' => date('Y-m-d H:i:s'),
                    'confidence' => $criterionData['confidence'],
                    'auto_linked' => true
                ];
            }
        }
    }
    
    // Save updated checklist
    file_put_contents($checklistDbFile, json_encode($checklistDb, JSON_PRETTY_PRINT));
}


// Perform content analysis (simplified version)
function performContentAnalysis($content) {
    $analysis = [
        'satisfied_criteria' => []
    ];
    
    $contentLower = strtolower($content);
    
    // Criteria keywords mapping
    $criteriaKeywords = [
        'Champion Bold Innovation' => ['champion', 'bold', 'innovation', 'innovative', 'breakthrough', 'pioneering', 'cutting-edge'],
        'Cultivate Global Citizens' => ['cultivate', 'global', 'citizens', 'citizenship', 'international', 'cross-cultural'],
        'Nurture Lifelong Learning' => ['nurture', 'lifelong', 'learning', 'education', 'development', 'growth'],
        'Lead with Purpose' => ['lead', 'purpose', 'leadership', 'vision', 'mission', 'goals'],
        'Ethical and Inclusive Leadership' => ['ethical', 'inclusive', 'leadership', 'diversity', 'equity', 'fairness'],
        'Expand Access to Global Opportunities' => ['expand', 'access', 'global', 'opportunities', 'international', 'programs'],
        'Foster Collaborative Innovation' => ['foster', 'collaborative', 'innovation', 'partnership', 'cooperation'],
        'Embrace Inclusivity and Beyond' => ['embrace', 'inclusivity', 'inclusive', 'diversity', 'equity'],
        'Innovation' => ['innovation', 'innovative', 'creative', 'new', 'breakthrough'],
        'Strategic and Inclusive Growth' => ['strategic', 'inclusive', 'growth', 'development', 'expansion'],
        'Empowerment of Others' => ['empowerment', 'empower', 'mentoring', 'support', 'guidance'],
        'Comprehensive Internationalization Efforts' => ['comprehensive', 'internationalization', 'international', 'global', 'efforts'],
        'Cooperation and Collaboration' => ['cooperation', 'collaboration', 'partnership', 'teamwork'],
        'Measurable Impact' => ['measurable', 'impact', 'results', 'outcomes', 'achievements'],
        'Ignite Intercultural Understanding' => ['ignite', 'intercultural', 'understanding', 'cultural', 'diversity'],
        'Empower Changemakers' => ['empower', 'changemakers', 'change', 'transformation', 'impact'],
        'Cultivate Active Engagement' => ['cultivate', 'active', 'engagement', 'participation', 'involvement']
    ];
    
    // Check each criterion
    foreach ($criteriaKeywords as $criterion => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            if (strpos($contentLower, $keyword) !== false) {
                $score += 1;
            }
        }
        
        if ($score > 0) {
            // Determine award type from criterion
            $awardType = '';
            if (in_array($criterion, ['Champion Bold Innovation', 'Cultivate Global Citizens', 'Nurture Lifelong Learning', 'Lead with Purpose', 'Ethical and Inclusive Leadership'])) {
                $awardType = 'leadership';
            } elseif (in_array($criterion, ['Expand Access to Global Opportunities', 'Foster Collaborative Innovation', 'Embrace Inclusivity and Beyond'])) {
                $awardType = 'education';
            } elseif (in_array($criterion, ['Innovation', 'Strategic and Inclusive Growth', 'Empowerment of Others'])) {
                $awardType = 'emerging';
            } elseif (in_array($criterion, ['Comprehensive Internationalization Efforts', 'Cooperation and Collaboration', 'Measurable Impact'])) {
                $awardType = 'regional';
            } elseif (in_array($criterion, ['Ignite Intercultural Understanding', 'Empower Changemakers', 'Cultivate Active Engagement'])) {
                $awardType = 'global';
            }
            
            if ($awardType) {
                $analysis['satisfied_criteria'][] = [
                    'award_type' => $awardType,
                    'criterion' => $criterion,
                    'confidence' => min(100, ($score / count($keywords)) * 100)
                ];
            }
        }
    }
    
    return $analysis;
}

// Update award counters and readiness
function updateAwardCounters($awardType, $contentType) {
    $countersDbFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'award_counters.json';
    
    // Load or create counters database
    if (!file_exists($countersDbFile)) {
        $counters = [
            'leadership' => ['documents' => 0, 'events' => 0, 'threshold' => 5],
            'education' => ['documents' => 0, 'events' => 0, 'threshold' => 3],
            'emerging' => ['documents' => 0, 'events' => 0, 'threshold' => 3],
            'regional' => ['documents' => 0, 'events' => 0, 'threshold' => 4],
            'global' => ['documents' => 0, 'events' => 0, 'threshold' => 3]
        ];
    } else {
        $counters = json_decode(file_get_contents($countersDbFile), true);
        if (!is_array($counters)) {
            $counters = [
                'leadership' => ['documents' => 0, 'events' => 0, 'threshold' => 5],
                'education' => ['documents' => 0, 'events' => 0, 'threshold' => 3],
                'emerging' => ['documents' => 0, 'events' => 0, 'threshold' => 3],
                'regional' => ['documents' => 0, 'events' => 0, 'threshold' => 4],
                'global' => ['documents' => 0, 'events' => 0, 'threshold' => 3]
            ];
        }
    }
    
    // Update counter
    if ($contentType === 'document') {
        $counters[$awardType]['documents']++;
    } elseif ($contentType === 'event') {
        $counters[$awardType]['events']++;
    }
    
    // Calculate readiness
    $totalContent = $counters[$awardType]['documents'] + $counters[$awardType]['events'];
    $counters[$awardType]['readiness'] = $totalContent >= $counters[$awardType]['threshold'] ? 'Ready to Apply' : 'Incomplete';
    $counters[$awardType]['total_content'] = $totalContent;
    $counters[$awardType]['last_updated'] = date('Y-m-d H:i:s');
    
    // Save updated counters
    file_put_contents($countersDbFile, json_encode($counters, JSON_PRETTY_PRINT));
    
    return $counters[$awardType];
}

// Get award counters
function getAwardCounters() {
    $countersDbFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'award_counters.json';
    
    if (!file_exists($countersDbFile)) {
        return [
            'leadership' => ['documents' => 0, 'events' => 0, 'threshold' => 5, 'readiness' => 'Incomplete', 'total_content' => 0],
            'education' => ['documents' => 0, 'events' => 0, 'threshold' => 3, 'readiness' => 'Incomplete', 'total_content' => 0],
            'emerging' => ['documents' => 0, 'events' => 0, 'threshold' => 3, 'readiness' => 'Incomplete', 'total_content' => 0],
            'regional' => ['documents' => 0, 'events' => 0, 'threshold' => 4, 'readiness' => 'Incomplete', 'total_content' => 0],
            'global' => ['documents' => 0, 'events' => 0, 'threshold' => 3, 'readiness' => 'Incomplete', 'total_content' => 0]
        ];
    }
    
    $counters = json_decode(file_get_contents($countersDbFile), true);
    
    // Recalculate readiness for all awards
    foreach ($counters as $awardType => &$data) {
        $totalContent = $data['documents'] + $data['events'];
        $data['total_content'] = $totalContent;
        $data['readiness'] = $totalContent >= $data['threshold'] ? 'Ready to Apply' : 'Incomplete';
    }
    
    return $counters;
}

// Recalculate all counters from actual data
function recalculateAllCounters() {
    $countersDbFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'award_counters.json';
    
    // Get actual counts from documents and events
    $documentsDb = json_decode(file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'documents.json'), true);
    $eventsDb = json_decode(file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'events.json'), true);
    
    $counters = [
        'leadership' => ['documents' => 0, 'events' => 0, 'threshold' => 5],
        'education' => ['documents' => 0, 'events' => 0, 'threshold' => 3],
        'emerging' => ['documents' => 0, 'events' => 0, 'threshold' => 3],
        'regional' => ['documents' => 0, 'events' => 0, 'threshold' => 4],
        'global' => ['documents' => 0, 'events' => 0, 'threshold' => 3]
    ];
    
    // Count documents
    if (isset($documentsDb['documents'])) {
        foreach ($documentsDb['documents'] as $doc) {
            if ($doc['status'] === 'Active' && !empty($doc['award_type'])) {
                $awardType = $doc['award_type'];
                if (isset($counters[$awardType])) {
                    $counters[$awardType]['documents']++;
                }
            }
        }
    }
    
    // Count events
    if (isset($eventsDb['events'])) {
        foreach ($eventsDb['events'] as $event) {
            if ($event['status'] === 'Active' && !empty($event['award_type'])) {
                $awardType = $event['award_type'];
                if (isset($counters[$awardType])) {
                    $counters[$awardType]['events']++;
                }
            }
        }
    }
    
    // Calculate readiness for all awards
    foreach ($counters as $awardType => &$data) {
        $totalContent = $data['documents'] + $data['events'];
        $data['total_content'] = $totalContent;
        $data['readiness'] = $totalContent >= $data['threshold'] ? 'Ready to Apply' : 'Incomplete';
        $data['last_updated'] = date('Y-m-d H:i:s');
    }
    
    // Save updated counters
    file_put_contents($countersDbFile, json_encode($counters, JSON_PRETTY_PRINT));
    
    return $counters;
}


