<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Debug: Test if API is working
if (!isset($_GET['action'])) {
    echo json_encode(['success' => true, 'message' => 'API is working', 'debug' => true]);
    exit;
}

// Debug: Test specific action
if ($_GET['action'] === 'test') {
    echo json_encode(['success' => true, 'message' => 'Test action working', 'action' => $_GET['action']]);
    exit;
}

// Debug: Test database connection
if ($_GET['action'] === 'test_db') {
    require_once __DIR__ . '/../config/database.php';
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        echo json_encode(['success' => true, 'message' => 'Database connection successful']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Debug: Log the action
error_log("DEBUG: Action received: " . $action);

function respond($success, $data = []) {
    echo json_encode(['success' => $success] + $data);
    exit;
}

// Function to analyze content against CHED criteria
function analyzeContentAgainstCHEDCriteria($content, $awardType, $pdo) {
    $content = strtolower($content);
    
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
    
    if (!isset($criteria[$awardType])) {
        return ['satisfied' => [], 'unsatisfied' => []];
    }
    
    $satisfied = [];
    $unsatisfied = [];
    
    foreach ($criteria[$awardType] as $keyword) {
        if (strpos($content, strtolower($keyword)) !== false) {
            $satisfied[] = $keyword;
        } else {
            $unsatisfied[] = $keyword;
        }
    }
    
    return [
        'satisfied' => $satisfied,
        'unsatisfied' => $unsatisfied
    ];
}

if ($action === 'analyze_all_content') {
    // Load database connection
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
    } catch (Exception $e) {
        respond(false, ['message' => 'Database connection failed: ' . $e->getMessage()]);
    }
    
    // Reset all counters
    $pdo->exec("UPDATE award_readiness SET 
        total_documents = 0, 
        total_events = 0, 
        total_items = 0,
        satisfied_criteria = '[]',
        readiness_percentage = 0,
        is_ready = 0");
    
    $analysisResults = [];
    
    // Analyze documents from enhanced_documents table (where content extraction happens)
    $stmt = $pdo->query("SELECT id, document_name, filename, category, extracted_content FROM enhanced_documents");
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($docs as $doc) {
        $content = $doc['document_name'] . ' ' . $doc['filename'] . ' ' . $doc['category'] . ' ' . ($doc['extracted_content'] ?? '');
        
        // Special handling for MOU documents - they should match multiple award criteria
        if ($doc['category'] === 'MOUs & MOAs') {
            // MOU documents typically match Regional and Citizenship awards
            $mouAwardTypes = ['regional', 'citizenship', 'leadership'];
            foreach ($mouAwardTypes as $awardType) {
                $analysis = analyzeContentAgainstCHEDCriteria($content, $awardType, $pdo);
                
                if (!empty($analysis['satisfied'])) {
                    // Update counters
                    $stmt = $pdo->prepare("UPDATE award_readiness SET 
                        total_documents = total_documents + 1,
                        total_items = total_items + 1,
                        last_calculated = NOW()
                        WHERE award_key = ?");
                    $stmt->execute([$awardType]);
                    
                    $analysisResults[] = [
                        'type' => 'mou_document',
                        'name' => $doc['document_name'],
                        'award' => $awardType,
                        'satisfied_criteria' => $analysis['satisfied']
                    ];
                }
            }
        } else {
            // Regular documents
            $awardTypes = ['leadership', 'education', 'emerging', 'regional', 'citizenship'];
            foreach ($awardTypes as $awardType) {
                $analysis = analyzeContentAgainstCHEDCriteria($content, $awardType, $pdo);
                
                if (!empty($analysis['satisfied'])) {
                    // Update counters
                    $stmt = $pdo->prepare("UPDATE award_readiness SET 
                        total_documents = total_documents + 1,
                        total_items = total_items + 1,
                        last_calculated = NOW()
                        WHERE award_key = ?");
                    $stmt->execute([$awardType]);
                    
                    $analysisResults[] = [
                        'type' => 'document',
                        'name' => $doc['document_name'],
                        'award' => $awardType,
                        'satisfied_criteria' => $analysis['satisfied']
                    ];
                }
            }
        }
    }
    
    // Analyze events
    $stmt = $pdo->query("SELECT id, title, description FROM central_events WHERE status = 'completed'");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($events as $event) {
        $content = $event['title'] . ' ' . $event['description'];
        
        $awardTypes = ['leadership', 'education', 'emerging', 'regional', 'citizenship'];
        foreach ($awardTypes as $awardType) {
            $analysis = analyzeContentAgainstCHEDCriteria($content, $awardType, $pdo);
            
            if (!empty($analysis['satisfied'])) {
                // Update counters
                $stmt = $pdo->prepare("UPDATE award_readiness SET 
                    total_events = total_events + 1,
                    total_items = total_items + 1,
                    last_calculated = NOW()
                    WHERE award_key = ?");
                $stmt->execute([$awardType]);
                
                $analysisResults[] = [
                    'type' => 'event',
                    'name' => $event['title'],
                    'award' => $awardType,
                    'satisfied_criteria' => $analysis['satisfied']
                ];
            }
        }
    }
    
    // Update readiness percentages
    $stmt = $pdo->query("SELECT award_key, total_items, threshold FROM award_readiness ar 
                        JOIN award_types at ON ar.award_key = at.award_key");
    $awards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($awards as $award) {
        $readinessPercentage = min(100, ($award['total_items'] / $award['threshold']) * 100);
        $isReady = $award['total_items'] >= $award['threshold'];
        
        $stmt = $pdo->prepare("UPDATE award_readiness SET 
            readiness_percentage = ?,
            is_ready = ?
            WHERE award_key = ?");
        $stmt->execute([$readinessPercentage, $isReady ? 1 : 0, $award['award_key']]);
    }
    
    respond(true, [
        'message' => 'Analysis completed successfully',
        'results' => $analysisResults,
        'total_analyzed' => count($docs) + count($events)
    ]);
}

if ($action === 'get_readiness_summary') {
    // Debug: Log that we're in this action
    error_log("DEBUG: Entering get_readiness_summary action");
    
    // Load database connection
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
    } catch (Exception $e) {
        respond(false, ['message' => 'Database connection failed: ' . $e->getMessage()]);
    }
    
    // Check if tables exist, create if not
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS award_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        award_key VARCHAR(50) UNIQUE NOT NULL,
        award_name VARCHAR(255) NOT NULL,
        criteria TEXT,
        keywords TEXT,
        threshold INT DEFAULT 5,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
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
    $defaultAwards = [
        ['leadership', 'Internationalization (IZN) Leadership', '["Champion Bold Innovation", "Cultivate Global Citizens", "Nurture Lifelong Learning", "Lead with Purpose", "Ethical and Inclusive Leadership"]', '["leadership", "innovation", "global", "learning", "purpose", "ethical"]'],
        ['education', 'Outstanding International Education Program', '["Foster Collaborative Innovation", "Embrace Inclusivity and Beyond", "Drive Academic Excellence", "Build Sustainable Partnerships"]', '["education", "collaboration", "inclusivity", "excellence", "partnerships"]'],
        ['emerging', 'Emerging Internationalization', '["Pioneer New Frontiers", "Adapt and Transform", "Build Capacity", "Create Impact"]', '["emerging", "pioneer", "adapt", "transform", "capacity", "impact"]'],
        ['regional', 'Regional Internationalization', '["Comprehensive Internationalization Efforts", "Cooperation and Collaboration", "Measurable Impact"]', '["regional", "internationalization", "cooperation", "collaboration", "impact"]'],
        ['citizenship', 'Global Citizenship', '["Ignite Intercultural Understanding", "Empower Changemakers", "Cultivate Active Engagement"]', '["citizenship", "intercultural", "understanding", "changemakers", "engagement"]']
    ];
    
    foreach ($defaultAwards as $award) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO award_types (award_key, award_name, criteria, keywords) VALUES (?, ?, ?, ?)");
        $stmt->execute($award);
    }
    
    // Insert default readiness records if not exist
    foreach ($defaultAwards as $award) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO award_readiness (award_key) VALUES (?)");
        $stmt->execute([$award[0]]);
    }
    
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_awards,
        SUM(CASE WHEN is_ready = 1 THEN 1 ELSE 0 END) as ready_awards,
        SUM(CASE WHEN is_ready = 0 THEN 1 ELSE 0 END) as incomplete_awards,
        SUM(total_documents) as total_documents,
        SUM(total_events) as total_events,
        SUM(total_items) as total_content
        FROM award_readiness");
    
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT award_key, total_documents, total_events, total_items, 
                        readiness_percentage, is_ready, satisfied_criteria 
                        FROM award_readiness ORDER BY award_key");
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    respond(true, [
        'summary' => $summary,
        'totals' => $totals
    ]);
    } catch (Exception $e) {
        respond(false, ['message' => 'Error in get_readiness_summary: ' . $e->getMessage()]);
    }
}

if ($action === 'get_checklist_status') {
    $awardType = $_GET['award_type'] ?? '';
    
    if (empty($awardType)) {
        respond(false, ['message' => 'Award type required']);
    }
    
    // Ensure tables exist first
    $pdo->exec("CREATE TABLE IF NOT EXISTS award_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        award_key VARCHAR(50) UNIQUE NOT NULL,
        award_name VARCHAR(255) NOT NULL,
        criteria TEXT,
        keywords TEXT,
        threshold INT DEFAULT 5,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $stmt = $pdo->prepare("SELECT criteria FROM award_types WHERE award_key = ?");
    $stmt->execute([$awardType]);
    $award = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$award) {
        respond(false, ['message' => 'Award type not found']);
    }
    
    $criteria = json_decode($award['criteria'], true);
    $status = [];
    
    if ($criteria) {
        foreach ($criteria as $criterion) {
            $status[] = [
                'criterion' => $criterion,
                'satisfied' => false // This would be determined by the analysis
            ];
        }
    }
    
    respond(true, ['status' => $status]);
}

if ($action === 'update_criterion_status') {
    respond(true, ['message' => 'Status updated']);
}

// Function to auto-analyze and update counters when content is uploaded
function autoAnalyzeContent($content, $contentType, $pdo) {
    $awardTypes = ['leadership', 'education', 'emerging', 'regional', 'citizenship'];
    $matches = [];
    
    foreach ($awardTypes as $awardType) {
        $analysis = analyzeContentAgainstCHEDCriteria($content, $awardType, $pdo);
        
        if (!empty($analysis['satisfied'])) {
            // Update counters immediately
            if ($contentType === 'document' || $contentType === 'mou') {
                $stmt = $pdo->prepare("UPDATE award_readiness SET 
                    total_documents = total_documents + 1,
                    total_items = total_items + 1,
                    last_calculated = NOW()
                    WHERE award_key = ?");
            } else if ($contentType === 'event') {
                $stmt = $pdo->prepare("UPDATE award_readiness SET 
                    total_events = total_events + 1,
                    total_items = total_items + 1,
                    last_calculated = NOW()
                    WHERE award_key = ?");
            }
            
            $stmt->execute([$awardType]);
            
            $matches[] = [
                'award' => $awardType,
                'satisfied_criteria' => $analysis['satisfied']
            ];
        }
    }
    
    return $matches;
}

if ($action === 'auto_analyze_upload') {
    $content = $_POST['content'] ?? '';
    $contentType = $_POST['content_type'] ?? 'document'; // document, event, mou
    
    if (empty($content)) {
        respond(false, ['message' => 'Content required']);
    }
    
    $matches = autoAnalyzeContent($content, $contentType, $pdo);
    
    respond(true, [
        'message' => 'Auto-analysis completed',
        'matches' => $matches,
        'total_matches' => count($matches)
    ]);
}

respond(true, ['message' => 'Action completed']);
?>
