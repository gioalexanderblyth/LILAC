<?php
/**
 * Awards Check API
 * Handles cross-module awards checking and criteria evaluation
 */

require_once '../config/database.php';
require_once '../classes/DateTimeUtility.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = new Database();
$pdo = $db->getConnection();

function api_respond($success, $data = [], $error = null) {
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit();
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

try {
    switch ($action) {
        case 'check_awards':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_respond(false, [], 'Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $type = sanitizeInput($input['type'] ?? '');
            $itemId = intval($input['item_id'] ?? 0);
            $additionalData = $input['additional_data'] ?? [];
            
            if (empty($type) || $itemId <= 0) {
                api_respond(false, [], 'Invalid parameters');
            }
            
            $awardsEarned = [];
            $progressUpdated = [];
            
            // Check different award criteria based on type
            switch ($type) {
                case 'award':
                    $result = checkAwardCriteria($pdo, $itemId, $additionalData);
                    $awardsEarned = array_merge($awardsEarned, $result['awards']);
                    $progressUpdated = array_merge($progressUpdated, $result['progress']);
                    break;
                    
                case 'event':
                    $result = checkEventCriteria($pdo, $itemId, $additionalData);
                    $awardsEarned = array_merge($awardsEarned, $result['awards']);
                    $progressUpdated = array_merge($progressUpdated, $result['progress']);
                    break;
                    
                case 'mou':
                    $result = checkMouCriteria($pdo, $itemId, $additionalData);
                    $awardsEarned = array_merge($awardsEarned, $result['awards']);
                    $progressUpdated = array_merge($progressUpdated, $result['progress']);
                    break;
                    
                case 'document':
                    $result = checkDocumentCriteria($pdo, $itemId, $additionalData);
                    $awardsEarned = array_merge($awardsEarned, $result['awards']);
                    $progressUpdated = array_merge($progressUpdated, $result['progress']);
                    break;
            }
            
            api_respond(true, [
                'awards_earned' => $awardsEarned,
                'progress_updated' => $progressUpdated
            ]);
            break;
            
        case 'check_specific_award':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_respond(false, [], 'Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $awardType = sanitizeInput($input['award_type'] ?? '');
            $criteria = $input['criteria'] ?? [];
            
            if (empty($awardType)) {
                api_respond(false, [], 'Invalid award type');
            }
            
            $award = checkSpecificAward($pdo, $awardType, $criteria);
            
            if ($award) {
                api_respond(true, ['award_earned' => $award]);
            } else {
                api_respond(true, ['award_earned' => null]);
            }
            break;
            
        case 'get_user_progress':
            $userId = 1; // TODO: Get from session/auth
            $progress = getUserProgress($pdo, $userId);
            api_respond(true, ['progress' => $progress]);
            break;
            
        default:
            api_respond(false, [], 'Invalid action');
            break;
    }
} catch (Exception $e) {
    error_log("Awards Check API Error: " . $e->getMessage());
    api_respond(false, [], 'Internal server error');
}

/**
 * Check award criteria for award documents
 */
function checkAwardCriteria($pdo, $awardId, $additionalData) {
    $awards = [];
    $progress = [];
    
    try {
        // Get award details
        $stmt = $pdo->prepare("SELECT * FROM awards WHERE id = ?");
        $stmt->execute([$awardId]);
        $award = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$award) {
            return ['awards' => $awards, 'progress' => $progress];
        }
        
        // Check for "First Award" achievement
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM awards WHERE id <= ?");
        $stmt->execute([$awardId]);
        $totalAwards = $stmt->fetchColumn();
        
        if ($totalAwards === 1) {
            $awards[] = [
                'name' => 'First Award',
                'category' => 'milestone',
                'description' => 'Congratulations on your first award!'
            ];
        }
        
        // Check for category-specific achievements
        $categoryCounts = [];
        $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM awards GROUP BY category");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categories as $cat) {
            $categoryCounts[$cat['category']] = $cat['count'];
        }
        
        // Check for "Category Master" achievements
        foreach ($categoryCounts as $category => $count) {
            if ($count >= 5) {
                $awards[] = [
                    'name' => ucfirst($category) . ' Master',
                    'category' => 'expertise',
                    'description' => "You've earned 5+ awards in the {$category} category!"
                ];
            }
        }
        
        // Update progress for various metrics
        $totalAwards = array_sum($categoryCounts);
        $progress[] = [
            'description' => 'Total Awards',
            'progress' => min(100, ($totalAwards / 10) * 100), // 10 awards = 100%
            'current' => $totalAwards,
            'target' => 10
        ];
        
    } catch (Exception $e) {
        error_log("Error checking award criteria: " . $e->getMessage());
    }
    
    return ['awards' => $awards, 'progress' => $progress];
}

/**
 * Check award criteria for events
 */
function checkEventCriteria($pdo, $eventId, $additionalData) {
    $awards = [];
    $progress = [];
    
    try {
        // Get event details
        $stmt = $pdo->prepare("SELECT * FROM central_events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            return ['awards' => $awards, 'progress' => $progress];
        }
        
        // Check for "Event Organizer" achievement
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM central_events WHERE id <= ?");
        $stmt->execute([$eventId]);
        $totalEvents = $stmt->fetchColumn();
        
        if ($totalEvents >= 3) {
            $awards[] = [
                'name' => 'Event Organizer',
                'category' => 'leadership',
                'description' => 'You\'ve organized 3+ events!'
            ];
        }
        
        // Update progress
        $progress[] = [
            'description' => 'Events Organized',
            'progress' => min(100, ($totalEvents / 5) * 100), // 5 events = 100%
            'current' => $totalEvents,
            'target' => 5
        ];
        
    } catch (Exception $e) {
        error_log("Error checking event criteria: " . $e->getMessage());
    }
    
    return ['awards' => $awards, 'progress' => $progress];
}

/**
 * Check award criteria for MOUs
 */
function checkMouCriteria($pdo, $mouId, $additionalData) {
    $awards = [];
    $progress = [];
    
    try {
        // Get MOU details
        $stmt = $pdo->prepare("SELECT * FROM mou_documents WHERE id = ?");
        $stmt->execute([$mouId]);
        $mou = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mou) {
            return ['awards' => $awards, 'progress' => $progress];
        }
        
        // Check for "Partnership Builder" achievement
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mou_documents WHERE id <= ?");
        $stmt->execute([$mouId]);
        $totalMous = $stmt->fetchColumn();
        
        if ($totalMous >= 2) {
            $awards[] = [
                'name' => 'Partnership Builder',
                'category' => 'collaboration',
                'description' => 'You\'ve established 2+ partnerships!'
            ];
        }
        
        // Update progress
        $progress[] = [
            'description' => 'Partnerships Established',
            'progress' => min(100, ($totalMous / 3) * 100), // 3 MOUs = 100%
            'current' => $totalMous,
            'target' => 3
        ];
        
    } catch (Exception $e) {
        error_log("Error checking MOU criteria: " . $e->getMessage());
    }
    
    return ['awards' => $awards, 'progress' => $progress];
}

/**
 * Check award criteria for documents
 */
function checkDocumentCriteria($pdo, $documentId, $additionalData) {
    $awards = [];
    $progress = [];
    
    try {
        // Get document details
        $stmt = $pdo->prepare("SELECT * FROM enhanced_documents WHERE id = ?");
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$document) {
            return ['awards' => $awards, 'progress' => $progress];
        }
        
        // Check for "Document Master" achievement
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enhanced_documents WHERE id <= ?");
        $stmt->execute([$documentId]);
        $totalDocuments = $stmt->fetchColumn();
        
        if ($totalDocuments >= 10) {
            $awards[] = [
                'name' => 'Document Master',
                'category' => 'organization',
                'description' => 'You\'ve uploaded 10+ documents!'
            ];
        }
        
        // Update progress
        $progress[] = [
            'description' => 'Documents Uploaded',
            'progress' => min(100, ($totalDocuments / 20) * 100), // 20 documents = 100%
            'current' => $totalDocuments,
            'target' => 20
        ];
        
    } catch (Exception $e) {
        error_log("Error checking document criteria: " . $e->getMessage());
    }
    
    return ['awards' => $awards, 'progress' => $progress];
}

/**
 * Check specific award criteria
 */
function checkSpecificAward($pdo, $awardType, $criteria) {
    // Implementation for specific award checking
    // This would contain the logic for checking specific award types
    return null;
}

/**
 * Get user progress
 */
function getUserProgress($pdo, $userId) {
    $progress = [];
    
    try {
        // Get various metrics
        $metrics = [
            'awards' => 'SELECT COUNT(*) FROM awards',
            'events' => 'SELECT COUNT(*) FROM central_events',
            'mous' => 'SELECT COUNT(*) FROM mou_documents',
            'documents' => 'SELECT COUNT(*) FROM enhanced_documents'
        ];
        
        foreach ($metrics as $metric => $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            $progress[$metric] = [
                'count' => $count,
                'progress' => min(100, ($count / 10) * 100) // 10 items = 100%
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error getting user progress: " . $e->getMessage());
    }
    
    return $progress;
}
?>
