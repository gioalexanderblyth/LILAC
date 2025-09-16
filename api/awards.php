<?php
/**
 * Awards API with MySQL Database Storage
 * Provides award criteria-based file retrieval and counting
 */

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

// Include database config
require_once '../config/database.php';

// Database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    awards_respond(false, ['message' => 'Database connection failed: ' . $e->getMessage()]);
}

function awards_respond($ok, $payload = []) {
    echo json_encode(array_merge(['success' => $ok], $payload));
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// CHED Award Criteria Definitions
$AWARD_CRITERIA = [
    'leadership' => [
        'name' => 'Internationalization (IZN) Leadership Award',
        'keywords' => [
            'champion bold innovation', 'cultivate global citizens', 'nurture lifelong learning',
            'lead with purpose', 'ethical and inclusive leadership', 'internationalization',
            'leadership', 'innovation', 'global citizens', 'lifelong learning', 'purpose',
            'ethical', 'inclusive', 'bold', 'champion', 'cultivate', 'nurture'
        ]
    ],
    'education' => [
        'name' => 'Outstanding International Education Program Award',
        'keywords' => [
            'expand access to global opportunities', 'foster collaborative innovation',
            'embrace inclusivity and beyond', 'international education', 'global opportunities',
            'collaborative innovation', 'inclusivity', 'education program', 'academic',
            'curriculum', 'international', 'global', 'opportunities', 'collaborative'
        ]
    ],
    'emerging' => [
        'name' => 'Emerging Leadership Award',
        'keywords' => [
            'innovation', 'strategic and inclusive growth', 'empowerment of others',
            'emerging leadership', 'strategic growth', 'inclusive growth', 'empowerment',
            'emerging', 'strategic', 'inclusive', 'growth', 'empower', 'mentoring'
        ]
    ],
    'regional' => [
        'name' => 'Best Regional Office for Internationalization Award',
        'keywords' => [
            'comprehensive internationalization efforts', 'cooperation and collaboration',
            'measurable impact', 'regional office', 'internationalization efforts',
            'cooperation', 'collaboration', 'measurable impact', 'regional', 'office',
            'comprehensive', 'efforts', 'measurable', 'impact'
        ]
    ],
    'global' => [
        'name' => 'Global Citizenship Award',
        'keywords' => [
            'ignite intercultural understanding', 'empower changemakers',
            'cultivate active engagement', 'global citizenship', 'intercultural understanding',
            'changemakers', 'active engagement', 'citizenship', 'intercultural',
            'understanding', 'changemakers', 'engagement', 'ignite', 'empower', 'cultivate'
        ]
    ]
];

if ($action === 'get_awards' || $action === 'get_all' || $action === 'list') {
    try {
        // Get all documents from MySQL database
        $sql = "SELECT * FROM documents ORDER BY upload_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Transform data and categorize by award criteria
        $awardFiles = [];
        $awardCounts = [
            'leadership' => 0,
            'education' => 0,
            'emerging' => 0,
            'regional' => 0,
            'global' => 0,
            'total' => 0
        ];
        
        foreach ($files as $file) {
            $extractedText = strtolower($file['description'] ?? '');
            $filename = strtolower($file['document_name'] ?? '');
            $content = $extractedText . ' ' . $filename;
            
            // Check which award criteria this file matches
            $matchedAwards = [];
            foreach ($AWARD_CRITERIA as $awardKey => $awardData) {
                $score = 0;
                foreach ($awardData['keywords'] as $keyword) {
                    $score += substr_count($content, strtolower($keyword));
                }
                if ($score > 0) {
                    $matchedAwards[] = $awardKey;
                    $awardCounts[$awardKey]++;
                }
            }
            
            if (!empty($matchedAwards)) {
                $awardFiles[] = [
                    'id' => $file['id'],
                    'filename' => $file['document_name'],
                    'file_path' => $file['filename'],
                    'upload_date' => $file['upload_date'],
                    'matched_awards' => $matchedAwards,
                    'linked_pages' => [],
                    'extracted_text' => $file['description']
                ];
                $awardCounts['total']++;
            }
        }
        
        // If no files match award criteria, return empty data (no files uploaded yet)
        if (empty($awardFiles)) {
            $awardFiles = [];
            $awardCounts = [
                'leadership' => 0,
                'education' => 0,
                'emerging' => 0,
                'regional' => 0,
                'global' => 0,
                'total' => 0
            ];
        }
        
        awards_respond(true, [
            'awards' => $awardFiles,
            'files' => $awardFiles,
            'counts' => $awardCounts,
            'criteria' => $AWARD_CRITERIA
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in get_awards: " . $e->getMessage());
        awards_respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'get_by_criteria') {
    $criteria = $_GET['criteria'] ?? '';
    if (empty($criteria) || !isset($AWARD_CRITERIA[$criteria])) {
        awards_respond(false, ['message' => 'Invalid award criteria']);
    }
    
    try {
        $awardData = $AWARD_CRITERIA[$criteria];
        $keywords = $awardData['keywords'];
        
        // Get files that match this specific award criteria
        $sql = "SELECT * FROM documents";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $matchingFiles = [];
        foreach ($files as $file) {
            $extractedText = strtolower($file['description'] ?? '');
            $filename = strtolower($file['document_name'] ?? '');
            $content = $extractedText . ' ' . $filename;
            
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($content, strtolower($keyword));
            }
            
            if ($score > 0) {
                $matchingFiles[] = [
                    'id' => $file['id'],
                    'filename' => $file['document_name'],
                    'file_path' => $file['filename'],
                    'upload_date' => $file['upload_date'],
                    'match_score' => $score,
                    'linked_pages' => []
                ];
            }
        }
        
        // Sort by match score (highest first)
        usort($matchingFiles, function($a, $b) {
            return $b['match_score'] - $a['match_score'];
        });
        
        awards_respond(true, [
            'criteria' => $criteria,
            'award_name' => $awardData['name'],
            'files' => $matchingFiles,
            'count' => count($matchingFiles)
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in get_by_criteria: " . $e->getMessage());
        awards_respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'get_counts') {
    try {
        $counts = [];
        
        foreach ($AWARD_CRITERIA as $awardKey => $awardData) {
            $keywords = $awardData['keywords'];
            
            $sql = "SELECT * FROM documents";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $count = 0;
            foreach ($files as $file) {
                $extractedText = strtolower($file['extracted_text'] ?? '');
                $filename = strtolower($file['original_filename'] ?? '');
                $content = $extractedText . ' ' . $filename;
                
                $score = 0;
                foreach ($keywords as $keyword) {
                    $score += substr_count($content, strtolower($keyword));
                }
                
                if ($score > 0) {
                    $count++;
                }
            }
            
            $counts[$awardKey] = [
                'name' => $awardData['name'],
                'count' => $count
            ];
        }
        
        awards_respond(true, ['counts' => $counts]);
        
    } catch (PDOException $e) {
        error_log("Database error in get_counts: " . $e->getMessage());
        awards_respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'get_awards_by_period') {
    $period = $_GET['period'] ?? '';
    
    // Return received awards for this year (mock data for now)
    $receivedAwards = [
        [
            'id' => 1,
            'title' => 'Outstanding International Education Program Award',
            'recipient' => 'LILAC System',
            'date' => '2024-11-15',
            'category' => 'Education',
            'amount' => '$5,000',
            'status' => 'active',
            'description' => 'Awarded for excellence in international education programs'
        ],
        [
            'id' => 2,
            'title' => 'Regional Internationalization Award',
            'recipient' => 'LILAC System',
            'date' => '2024-10-20',
            'category' => 'Regional',
            'amount' => '$3,000',
            'status' => 'active',
            'description' => 'Recognized for comprehensive regional internationalization efforts'
        ]
    ];
    
    awards_respond(true, ['awards' => $receivedAwards, 'period' => $period]);
}

if ($action === 'get_awards_by_month') {
    // For now, return empty data for month-based queries
    awards_respond(true, ['awards' => [], 'months' => []]);
}

// Default response
awards_respond(false, ['message' => 'Invalid action']);
?>