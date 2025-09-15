<?php
/**
 * Enhanced Documents API
 * Handles comprehensive document processing, award assignment, and readiness tracking
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Award types and their criteria
$AWARD_TYPES = [
    'leadership' => [
        'name' => 'Internationalization (IZN) Leadership Award',
        'criteria' => [
            'Champion Bold Innovation',
            'Cultivate Global Citizens', 
            'Nurture Lifelong Learning',
            'Lead with Purpose',
            'Ethical and Inclusive Leadership'
        ],
        'keywords' => [
            'leadership', 'internationalization', 'global', 'strategic', 'vision', 'innovation',
            'partnership', 'collaboration', 'exchange', 'program', 'initiative', 'development',
            'international', 'cross-cultural', 'cultural', 'diversity', 'inclusion', 'mentorship',
            'faculty', 'student', 'research', 'academic', 'institutional', 'governance',
            'policy', 'framework', 'strategy', 'planning', 'management', 'administration',
            'award', 'recognition', 'excellence', 'achievement', 'impact', 'outcome'
        ],
        'threshold' => 3
    ],
    'education' => [
        'name' => 'Outstanding International Education Program Award',
        'criteria' => [
            'Expand Access to Global Opportunities',
            'Foster Collaborative Innovation',
            'Embrace Inclusivity and Beyond'
        ],
        'keywords' => [
            'education', 'program', 'curriculum', 'academic', 'course', 'learning',
            'teaching', 'pedagogy', 'instruction', 'training', 'development', 'skill',
            'knowledge', 'expertise', 'competency', 'qualification', 'certification',
            'degree', 'diploma', 'certificate', 'study', 'research', 'scholarship',
            'international', 'global', 'cross-cultural', 'multicultural', 'diverse',
            'inclusive', 'accessible', 'opportunity', 'access', 'expand', 'foster'
        ],
        'threshold' => 2
    ],
    'emerging' => [
        'name' => 'Emerging Leadership Award',
        'criteria' => [
            'Innovation',
            'Strategic and Inclusive Growth',
            'Empowerment of Others'
        ],
        'keywords' => [
            'emerging', 'new', 'innovative', 'pioneering', 'cutting-edge', 'advanced',
            'modern', 'contemporary', 'current', 'latest', 'recent', 'fresh',
            'breakthrough', 'revolutionary', 'transformative', 'disruptive', 'creative',
            'original', 'unique', 'novel', 'unprecedented', 'groundbreaking',
            'strategic', 'growth', 'development', 'expansion', 'scaling', 'scalable',
            'empowerment', 'empower', 'enable', 'facilitate', 'support', 'assist'
        ],
        'threshold' => 2
    ],
    'regional' => [
        'name' => 'Best Regional Office for Internationalization Award',
        'criteria' => [
            'Comprehensive Internationalization Efforts',
            'Cooperation and Collaboration',
            'Measurable Impact'
        ],
        'keywords' => [
            'regional', 'region', 'local', 'area', 'district', 'province', 'state',
            'territory', 'zone', 'office', 'branch', 'center', 'centre', 'hub',
            'headquarters', 'base', 'location', 'site', 'facility', 'institution',
            'comprehensive', 'complete', 'full', 'total', 'entire', 'whole',
            'cooperation', 'collaboration', 'partnership', 'alliance', 'network',
            'coordination', 'coordinate', 'manage', 'administration', 'governance',
            'impact', 'effect', 'result', 'outcome', 'achievement', 'success'
        ],
        'threshold' => 2
    ],
    'citizenship' => [
        'name' => 'Global Citizenship Award',
        'criteria' => [
            'Ignite Intercultural Understanding',
            'Empower Changemakers',
            'Cultivate Active Engagement'
        ],
        'keywords' => [
            'citizenship', 'citizen', 'community', 'society', 'social', 'civic',
            'public', 'civil', 'democratic', 'participatory', 'engagement', 'involvement',
            'participation', 'contribution', 'service', 'volunteer', 'activism',
            'advocacy', 'awareness', 'consciousness', 'understanding', 'knowledge',
            'cultural', 'intercultural', 'multicultural', 'diversity', 'inclusion',
            'tolerance', 'respect', 'acceptance', 'appreciation', 'celebration',
            'ignite', 'spark', 'inspire', 'motivate', 'encourage', 'stimulate'
        ],
        'threshold' => 2
    ]
];

function respond($success, $data = []) {
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

function analyzeContentForAwards($content, $title = '') {
    global $AWARD_TYPES;
    
    $normalizedContent = strtolower($content . ' ' . $title);
    $analysis = [];
    
    foreach ($AWARD_TYPES as $awardType => $awardData) {
        $score = 0;
        $matchedKeywords = [];
        $satisfiedCriteria = [];
        
        // Check keyword matches
        foreach ($awardData['keywords'] as $keyword) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match_all($pattern, $normalizedContent, $matches)) {
                $score += count($matches[0]);
                $matchedKeywords[] = $keyword;
            }
        }
        
        // Check criteria satisfaction
        foreach ($awardData['criteria'] as $criterion) {
            $criterionLower = strtolower($criterion);
            $keywords = array_filter(explode(' ', $criterionLower), function($word) {
                return strlen($word) > 2;
            });
            
            $matchedCriterionKeywords = array_filter($keywords, function($keyword) use ($normalizedContent) {
                return strpos($normalizedContent, $keyword) !== false || 
                       strpos($normalizedContent, preg_replace('/[^a-z0-9]/', '', $keyword)) !== false;
            });
            
            if (count($matchedCriterionKeywords) >= (count($keywords) * 0.5)) {
                $satisfiedCriteria[] = $criterion;
                $score += 5; // Bonus points for satisfying criteria
            }
        }
        
        $analysis[$awardType] = [
            'score' => $score,
            'matchedKeywords' => $matchedKeywords,
            'satisfiedCriteria' => $satisfiedCriteria,
            'confidence' => min($score / 10, 1.0) // Normalize confidence
        ];
    }
    
    return $analysis;
}

function determineAwardAssignments($analysis) {
    global $AWARD_TYPES;
    
    $assignments = [];
    $threshold = 0.2; // Minimum confidence threshold
    
    foreach ($analysis as $awardType => $data) {
        if ($data['confidence'] >= $threshold) {
            $assignments[] = [
                'awardType' => $awardType,
                'awardName' => $AWARD_TYPES[$awardType]['name'],
                'confidence' => $data['confidence'],
                'score' => $data['score'],
                'matchedKeywords' => $data['matchedKeywords'],
                'satisfiedCriteria' => $data['satisfiedCriteria']
            ];
        }
    }
    
    // Sort by confidence
    usort($assignments, function($a, $b) {
        return $b['confidence'] <=> $a['confidence'];
    });
    
    return $assignments;
}

function calculateReadinessStatus($awardType, $assignedItems) {
    global $AWARD_TYPES;
    
    $awardData = $AWARD_TYPES[$awardType];
    $satisfiedCriteria = [];
    $unsatisfiedCriteria = [];
    
    // Check each criterion
    foreach ($awardData['criteria'] as $criterion) {
        $isSatisfied = false;
        
        foreach ($assignedItems as $item) {
            $content = strtolower($item['content'] ?? '');
            $criterionLower = strtolower($criterion);
            $keywords = array_filter(explode(' ', $criterionLower), function($word) {
                return strlen($word) > 2;
            });
            
            $matchedKeywords = array_filter($keywords, function($keyword) use ($content) {
                return strpos($content, $keyword) !== false || 
                       strpos($content, preg_replace('/[^a-z0-9]/', '', $keyword)) !== false;
            });
            
            if (count($matchedKeywords) >= (count($keywords) * 0.5)) {
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
    
    $readinessPercentage = (count($satisfiedCriteria) / count($awardData['criteria'])) * 100;
    $totalItems = count($assignedItems);
    $isReady = $totalItems >= $awardData['threshold'] && $readinessPercentage >= 80;
    
    return [
        'isReady' => $isReady,
        'satisfiedCriteria' => $satisfiedCriteria,
        'unsatisfiedCriteria' => $unsatisfiedCriteria,
        'readinessPercentage' => $readinessPercentage,
        'totalItems' => $totalItems,
        'threshold' => $awardData['threshold']
    ];
}

function generateRecommendations($awardType, $readiness) {
    global $AWARD_TYPES;
    
    $recommendations = [];
    $awardData = $AWARD_TYPES[$awardType];
    
    // Check if threshold is met
    if ($readiness['totalItems'] < $awardData['threshold']) {
        $recommendations[] = [
            'type' => 'quantity',
            'awardType' => $awardType,
            'awardName' => $awardData['name'],
            'message' => "Need " . ($awardData['threshold'] - $readiness['totalItems']) . " more document(s) or event(s) to meet minimum threshold",
            'priority' => 'high'
        ];
    }
    
    // Check unsatisfied criteria
    foreach ($readiness['unsatisfiedCriteria'] as $criterion) {
        $recommendations[] = [
            'type' => 'criteria',
            'awardType' => $awardType,
            'awardName' => $awardData['name'],
            'criterion' => $criterion,
            'message' => "Missing content demonstrating: " . $criterion,
            'suggestion' => generateContentSuggestion($criterion, $awardType),
            'priority' => 'medium'
        ];
    }
    
    return $recommendations;
}

function generateContentSuggestion($criterion, $awardType) {
    $suggestions = [
        'Champion Bold Innovation' => 'Create documents or events showcasing innovative international programs, cutting-edge research collaborations, or pioneering educational initiatives.',
        'Cultivate Global Citizens' => 'Document student exchange programs, cultural immersion activities, or global citizenship education initiatives.',
        'Nurture Lifelong Learning' => 'Showcase continuing education programs, professional development opportunities, or alumni engagement activities.',
        'Lead with Purpose' => 'Document strategic planning initiatives, vision statements, or leadership development programs.',
        'Ethical and Inclusive Leadership' => 'Showcase diversity and inclusion programs, ethical guidelines, or inclusive policy implementations.',
        'Expand Access to Global Opportunities' => 'Document scholarship programs, international partnerships, or accessibility initiatives.',
        'Foster Collaborative Innovation' => 'Showcase joint research projects, international collaborations, or innovative program partnerships.',
        'Embrace Inclusivity and Beyond' => 'Document inclusive practices, diversity initiatives, or equity-focused programs.',
        'Innovation' => 'Create content highlighting new approaches, creative solutions, or breakthrough initiatives.',
        'Strategic and Inclusive Growth' => 'Document growth strategies, expansion plans, or inclusive development programs.',
        'Empowerment of Others' => 'Showcase mentoring programs, capacity building initiatives, or empowerment-focused activities.',
        'Comprehensive Internationalization Efforts' => 'Document holistic internationalization strategies, comprehensive program portfolios, or integrated approaches.',
        'Cooperation and Collaboration' => 'Showcase partnership agreements, collaborative projects, or cooperative initiatives.',
        'Measurable Impact' => 'Document outcomes, metrics, success stories, or quantifiable results.',
        'Ignite Intercultural Understanding' => 'Showcase cultural exchange programs, intercultural dialogue initiatives, or cultural awareness activities.',
        'Empower Changemakers' => 'Document leadership development programs, change initiatives, or empowerment-focused activities.',
        'Cultivate Active Engagement' => 'Showcase community engagement programs, participatory initiatives, or active involvement activities.'
    ];
    
    return $suggestions[$criterion] ?? "Create content that demonstrates " . strtolower($criterion) . ".";
}

// Initialize database connection
try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Create enhanced documents table if it doesn't exist
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
    
    // Create enhanced events table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS enhanced_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATE,
        location VARCHAR(255),
        image_path VARCHAR(500),
        extracted_content LONGTEXT,
        award_assignments JSON,
        analysis_data JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
} catch (Exception $e) {
    respond(false, ['message' => 'Database connection failed: ' . $e->getMessage()]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'process_document':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(false, ['message' => 'Method not allowed']);
        }
        
        $documentName = trim($_POST['document_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $extractedContent = trim($_POST['extracted_content'] ?? '');
        
        if (empty($documentName) || empty($extractedContent)) {
            respond(false, ['message' => 'Document name and content are required']);
        }
        
        // Analyze content for awards
        $analysis = analyzeContentForAwards($extractedContent, $documentName);
        $assignments = determineAwardAssignments($analysis);
        
        // Store in database
        try {
            $stmt = $pdo->prepare("INSERT INTO enhanced_documents (document_name, filename, file_path, file_size, file_type, description, extracted_content, award_assignments, analysis_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $documentName,
                $documentName,
                'uploads/' . $documentName,
                0, // File size not available in this context
                'text/plain',
                $description,
                $extractedContent,
                json_encode($assignments),
                json_encode($analysis)
            ]);
            
            $documentId = $pdo->lastInsertId();
            
            respond(true, [
                'message' => 'Document processed successfully',
                'document_id' => $documentId,
                'analysis' => $analysis,
                'assignments' => $assignments
            ]);
            
        } catch (Exception $e) {
            respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'process_event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(false, ['message' => 'Method not allowed']);
        }
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eventDate = $_POST['event_date'] ?? null;
        $location = trim($_POST['location'] ?? '');
        
        if (empty($title)) {
            respond(false, ['message' => 'Event title is required']);
        }
        
        // Combine title and description for analysis
        $content = $title . ' ' . $description;
        
        // Analyze content for awards
        $analysis = analyzeContentForAwards($content, $title);
        $assignments = determineAwardAssignments($analysis);
        
        // Store in database
        try {
            $stmt = $pdo->prepare("INSERT INTO enhanced_events (title, description, event_date, location, extracted_content, award_assignments, analysis_data) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $title,
                $description,
                $eventDate,
                $location,
                $content,
                json_encode($assignments),
                json_encode($analysis)
            ]);
            
            $eventId = $pdo->lastInsertId();
            
            respond(true, [
                'message' => 'Event processed successfully',
                'event_id' => $eventId,
                'analysis' => $analysis,
                'assignments' => $assignments
            ]);
            
        } catch (Exception $e) {
            respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_status_report':
        try {
            // Get all documents and events
            $documentsStmt = $pdo->query("SELECT * FROM enhanced_documents ORDER BY upload_date DESC");
            $documents = $documentsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $eventsStmt = $pdo->query("SELECT * FROM enhanced_events ORDER BY created_at DESC");
            $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate counters and readiness for each award
            $counters = [];
            $readiness = [];
            $assignedDocuments = [];
            $assignedEvents = [];
            
            foreach ($AWARD_TYPES as $awardType => $awardData) {
                $counters[$awardType] = [
                    'documents' => 0,
                    'events' => 0,
                    'total' => 0
                ];
                $assignedDocuments[$awardType] = [];
                $assignedEvents[$awardType] = [];
            }
            
            // Process documents
            foreach ($documents as $doc) {
                $assignments = json_decode($doc['award_assignments'], true) ?? [];
                foreach ($assignments as $assignment) {
                    $awardType = $assignment['awardType'];
                    $counters[$awardType]['documents']++;
                    $assignedDocuments[$awardType][] = $doc;
                }
            }
            
            // Process events
            foreach ($events as $event) {
                $assignments = json_decode($event['award_assignments'], true) ?? [];
                foreach ($assignments as $assignment) {
                    $awardType = $assignment['awardType'];
                    $counters[$awardType]['events']++;
                    $assignedEvents[$awardType][] = $event;
                }
            }
            
            // Calculate totals and readiness
            foreach ($AWARD_TYPES as $awardType => $awardData) {
                $counters[$awardType]['total'] = $counters[$awardType]['documents'] + $counters[$awardType]['events'];
                $allItems = array_merge($assignedDocuments[$awardType], $assignedEvents[$awardType]);
                $readiness[$awardType] = calculateReadinessStatus($awardType, $allItems);
            }
            
            // Generate recommendations
            $recommendations = [];
            foreach ($readiness as $awardType => $readinessData) {
                if (!$readinessData['isReady']) {
                    $recommendations = array_merge($recommendations, generateRecommendations($awardType, $readinessData));
                }
            }
            
            // Calculate summary
            $summary = [
                'totalDocuments' => array_sum(array_column($counters, 'documents')),
                'totalEvents' => array_sum(array_column($counters, 'events')),
                'totalItems' => array_sum(array_column($counters, 'total')),
                'readyAwards' => count(array_filter($readiness, function($r) { return $r['isReady']; })),
                'totalAwards' => count($AWARD_TYPES)
            ];
            
            respond(true, [
                'summary' => $summary,
                'counters' => $counters,
                'readiness' => $readiness,
                'assignedDocuments' => $assignedDocuments,
                'assignedEvents' => $assignedEvents,
                'recommendations' => $recommendations,
                'awardTypes' => $AWARD_TYPES
            ]);
            
        } catch (Exception $e) {
            respond(false, ['message' => 'Error generating status report: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_award_details':
        $awardType = $_GET['award_type'] ?? '';
        
        if (!isset($AWARD_TYPES[$awardType])) {
            respond(false, ['message' => 'Invalid award type']);
        }
        
        try {
            // Get documents assigned to this award
            $documentsStmt = $pdo->prepare("SELECT * FROM enhanced_documents WHERE JSON_CONTAINS(award_assignments, JSON_OBJECT('awardType', ?)) ORDER BY upload_date DESC");
            $documentsStmt->execute([$awardType]);
            $documents = $documentsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get events assigned to this award
            $eventsStmt = $pdo->prepare("SELECT * FROM enhanced_events WHERE JSON_CONTAINS(award_assignments, JSON_OBJECT('awardType', ?)) ORDER BY created_at DESC");
            $eventsStmt->execute([$awardType]);
            $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate readiness
            $allItems = array_merge($documents, $events);
            $readiness = calculateReadinessStatus($awardType, $allItems);
            
            // Generate recommendations
            $recommendations = [];
            if (!$readiness['isReady']) {
                $recommendations = generateRecommendations($awardType, $readiness);
            }
            
            respond(true, [
                'awardType' => $awardType,
                'awardData' => $AWARD_TYPES[$awardType],
                'documents' => $documents,
                'events' => $events,
                'readiness' => $readiness,
                'recommendations' => $recommendations
            ]);
            
        } catch (Exception $e) {
            respond(false, ['message' => 'Error getting award details: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_award_types':
        respond(true, ['awardTypes' => $AWARD_TYPES]);
        break;
        
    default:
        respond(false, ['message' => 'Invalid action']);
        break;
}
?>
