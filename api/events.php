<?php
/**
 * Events API for Award Management System
 * Handles event creation, analysis, and award classification
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function respond($success, $data = []) {
    echo json_encode(['success' => $success] + $data);
    exit();
}

function load_events_db() {
    $dbFile = '../data/events.json';
    if (!file_exists($dbFile)) {
        return ['auto_id' => 1, 'events' => []];
    }
    
    $content = file_get_contents($dbFile);
    if ($content === false) {
        error_log("Failed to read events.json file");
        return ['auto_id' => 1, 'events' => []];
    }
    
    $decoded = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in events.json: " . json_last_error_msg());
        return ['auto_id' => 1, 'events' => []];
    }
    
    return $decoded;
}

function save_events_db($db, $dbFile) {
    return file_put_contents($dbFile, json_encode($db, JSON_PRETTY_PRINT));
}

function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function detect_award_from_content($title, $description, $ocrText = '') {
    $text = strtolower($title . ' ' . $description . ' ' . $ocrText);
    
    $awardKeywords = [
        'Internationalization (IZN) Leadership Award' => [
            'leadership', 'internationalization', 'global', 'strategic', 'vision', 'innovation',
            'partnership', 'collaboration', 'exchange', 'program', 'initiative', 'development',
            'international', 'cross-cultural', 'cultural', 'diversity', 'inclusion', 'mentorship',
            'champion', 'bold', 'innovation', 'cultivate', 'global citizens', 'lifelong learning',
            'purpose', 'ethical', 'inclusive leadership'
        ],
        'Outstanding International Education Program Award' => [
            'education', 'program', 'curriculum', 'academic', 'course', 'learning',
            'teaching', 'pedagogy', 'instruction', 'training', 'development', 'skill',
            'knowledge', 'expertise', 'competency', 'qualification', 'certification',
            'expand', 'access', 'global opportunities', 'foster', 'collaborative', 'innovation',
            'embrace', 'inclusivity', 'beyond'
        ],
        'Emerging Leadership Award' => [
            'emerging', 'new', 'innovative', 'pioneering', 'cutting-edge', 'advanced',
            'modern', 'contemporary', 'current', 'latest', 'recent', 'fresh',
            'breakthrough', 'revolutionary', 'transformative', 'disruptive', 'creative',
            'innovation', 'strategic', 'inclusive', 'growth', 'empowerment', 'others'
        ],
        'Best Regional Office for Internationalization Award' => [
            'regional', 'region', 'local', 'area', 'district', 'province', 'state',
            'territory', 'zone', 'office', 'branch', 'center', 'centre', 'hub',
            'comprehensive', 'complete', 'full', 'total', 'entire', 'whole',
            'cooperation', 'collaboration', 'partnership', 'alliance', 'network',
            'coordination', 'coordinate', 'manage', 'administration', 'governance',
            'impact', 'effect', 'result', 'outcome', 'achievement', 'success',
            'measurable', 'quantifiable', 'assessable', 'evaluable'
        ],
        'Global Citizenship Award' => [
            'citizenship', 'citizen', 'community', 'society', 'social', 'civic',
            'public', 'civil', 'democratic', 'participatory', 'engagement', 'involvement',
            'participation', 'contribution', 'service', 'volunteer', 'activism',
            'advocacy', 'awareness', 'consciousness', 'understanding', 'knowledge',
            'cultural', 'intercultural', 'multicultural', 'diversity', 'inclusion',
            'ignite', 'intercultural', 'understanding', 'empower', 'changemakers',
            'cultivate', 'active', 'engagement'
        ]
    ];
    
    $scores = [];
    foreach ($awardKeywords as $award => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            $score += substr_count($text, $keyword);
        }
        $scores[$award] = $score;
    }
    
    $bestMatch = array_keys($scores, max($scores))[0];
    $confidence = max($scores) > 0 ? min(max($scores) / 5, 1.0) : 0;
    
    return [
        'award' => $bestMatch,
        'confidence' => $confidence,
        'scores' => $scores
    ];
}

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $db = load_events_db();
    $dbFile = '../data/events.json';

    if ($action === 'add') {
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $awardType = sanitize_input($_POST['award_type'] ?? '');
        
        if (empty($title)) {
            respond(false, ['message' => 'Event title is required']);
        }
        
        // Handle image upload if provided
        $imagePath = '';
        $ocrText = '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadsDir = '../uploads/events';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            $originalName = $_FILES['image']['name'];
            $safeName = sanitize_input($originalName);
                $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
            if (in_array($ext, $allowed)) {
                $unique = uniqid('event_', true) . '.' . $ext;
                $dest = $uploadsDir . DIRECTORY_SEPARATOR . $unique;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $imagePath = $unique;
                    
                    // Perform OCR on the image (basic implementation)
                    // In production, you would use a proper OCR service
                    $ocrText = 'OCR text extraction would be performed here';
                }
            }
        }
        
        // Auto-classify award if not provided
        if (empty($awardType)) {
            $analysis = detect_award_from_content($title, $description, $ocrText);
            if ($analysis['confidence'] > 0.3) {
                $awardType = $analysis['award'];
            }
        }
        
        $now = date('Y-m-d H:i:s');
        $id = $db['auto_id']++;
        
        $event = [
            'id' => $id,
            'title' => $title,
                'description' => $description,
            'image_path' => $imagePath,
            'ocr_text' => $ocrText,
            'award_type' => $awardType,
            'created_date' => $now,
            'status' => 'Active'
        ];
        
        $db['events'][] = $event;
        save_events_db($db, $dbFile);
        
        // Auto-update counters and checklist if award type was determined
        if (!empty($awardType)) {
            updateAwardCounters($awardType, 'event');
            autoUpdateChecklistForEvent($event);
        }
        
        respond(true, ['message' => 'Event added successfully', 'event' => $event]);
    }

    if ($action === 'get_all') {
        try {
            $events = array_filter($db['events'], function($event) {
                return isset($event['status']) && $event['status'] === 'Active';
            });
            
            respond(true, ['events' => array_values($events)]);
        } catch (Exception $e) {
            error_log("Error in get_all action: " . $e->getMessage());
            respond(false, ['message' => 'Error loading events: ' . $e->getMessage()]);
        }
    }

    if ($action === 'get_by_id') {
        $eventId = $_GET['id'] ?? '';
        if (empty($eventId)) {
            respond(false, ['message' => 'Event ID required']);
        }
        
        $event = null;
        foreach ($db['events'] as $e) {
            if ($e['id'] == $eventId && $e['status'] === 'Active') {
                $event = $e;
                break;
            }
        }
        
        if ($event) {
            respond(true, ['event' => $event]);
        } else {
            respond(false, ['message' => 'Event not found']);
        }
    }

    if ($action === 'get_by_award') {
        $awardType = $_GET['award_type'] ?? '';
        if (empty($awardType)) {
            respond(false, ['message' => 'Award type required']);
        }
        
        $events = array_filter($db['events'], function($event) use ($awardType) {
            return $event['status'] === 'Active' && $event['award_type'] === $awardType;
        });
        
        respond(true, ['events' => array_values($events)]);
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
            $counts[$key] = count(array_filter($db['events'], function($event) use ($awardName) {
                return $event['status'] === 'Active' && $event['award_type'] === $awardName;
            }));
        }
        
        respond(true, ['counts' => $counts]);
    }

    if ($action === 'analyze_event') {
        $eventId = $_POST['event_id'] ?? '';
        if (empty($eventId)) {
            respond(false, ['message' => 'Event ID required']);
        }
        
        $event = null;
        foreach ($db['events'] as $evt) {
            if ($evt['id'] == $eventId && $evt['status'] === 'Active') {
                $event = $evt;
                break;
            }
        }
        
        if (!$event) {
            respond(false, ['message' => 'Event not found']);
        }
        
        $analysis = detect_award_from_content($event['title'], $event['description'], $event['ocr_text']);
        
        respond(true, ['analysis' => $analysis]);
    }

    if ($action === 'get_combined_analysis') {
        $awardType = $_GET['award_type'] ?? '';
        if (empty($awardType)) {
            respond(false, ['message' => 'Award type required']);
        }
        
        // Get events for this award
        $events = array_filter($db['events'], function($event) use ($awardType) {
            return $event['status'] === 'Active' && $event['award_type'] === $awardType;
        });
        
        // Get documents for this award (from documents.json)
        $documentsDb = json_decode(file_get_contents('../data/documents.json'), true);
        $documents = array_filter($documentsDb['documents'], function($doc) use ($awardType) {
            return $doc['status'] === 'Active' && $doc['award_type'] === $awardType;
        });
        
        // Analyze criteria satisfaction
        $criteria = getAwardCriteria($awardType);
        $satisfiedCriteria = [];
        $unsatisfiedCriteria = [];
        
        $allContent = array_merge(
            array_map(function($event) {
                return [
                    'title' => $event['title'],
                    'description' => $event['description'],
                    'ocr_text' => $event['ocr_text'] ?? '',
                    'type' => 'event'
                ];
            }, $events),
            array_map(function($doc) {
                return [
                    'title' => $doc['document_name'],
                    'description' => $doc['description'] ?? '',
                    'ocr_text' => $doc['ocr_text'] ?? '',
                    'type' => 'document'
                ];
            }, $documents)
        );
        
        foreach ($criteria as $criterion) {
            $isSatisfied = false;
            foreach ($allContent as $content) {
                if (checkCriterionSatisfaction($content, $criterion)) {
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
            'events' => array_values($events),
            'documents' => array_values($documents),
            'event_count' => count($events),
            'document_count' => count($documents),
            'total_count' => count($events) + count($documents),
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
 * Helper function to check if content satisfies a criterion
 */
function checkCriterionSatisfaction($content, $criterion) {
    $text = strtolower($content['title'] . ' ' . $content['description'] . ' ' . $content['ocr_text']);
    $criterionLower = strtolower($criterion);
    
    $keywords = explode(' ', $criterionLower);
    $matchedKeywords = 0;
    
    foreach ($keywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            $matchedKeywords++;
        }
    }
    
    return $matchedKeywords >= (count($keywords) * 0.5);
}

// Auto-update checklist for uploaded event
function autoUpdateChecklistForEvent($event) {
    $awardType = $event['award_type'];
    if (empty($awardType)) {
        return;
    }
    
    // Load checklist database
    $checklistDbFile = '../data/checklist.json';
    if (!file_exists($checklistDbFile)) {
        return;
    }
    
    $checklistDb = json_decode(file_get_contents($checklistDbFile), true);
    if (!is_array($checklistDb)) {
        $checklistDb = ['criterion_links' => []];
    }
    
    if (!isset($checklistDb['criterion_links'])) {
        $checklistDb['criterion_links'] = [];
    }
    
    // Get criteria for this award type
    $criteria = getAwardCriteria($awardType);
    
    // Analyze event content to determine which criteria it satisfies
    $content = $event['title'] . ' ' . $event['description'] . ' ' . $event['ocr_text'];
    $analysis = performContentAnalysis($content);
    
    // Link event to satisfied criteria
    foreach ($analysis['satisfied_criteria'] as $criterionData) {
        if ($criterionData['award_type'] === $awardType) {
            $linkKey = $awardType . '_' . md5($criterionData['criterion']);
            $linkId = 'event_' . $event['id'];
            
            if (!isset($checklistDb['criterion_links'][$linkKey])) {
                $checklistDb['criterion_links'][$linkKey] = [];
            }
            
            $checklistDb['criterion_links'][$linkKey][$linkId] = [
                'award_type' => $awardType,
                'criterion' => $criterionData['criterion'],
                'content_id' => $event['id'],
                'content_type' => 'event',
                'linked_at' => date('Y-m-d H:i:s'),
                'confidence' => $criterionData['confidence'],
                'auto_linked' => true
            ];
        }
    }
    
    // Save updated checklist
    file_put_contents($checklistDbFile, json_encode($checklistDb, JSON_PRETTY_PRINT));
}

// Get award criteria (helper function)
function getAwardCriteria($awardType) {
    $criteriaMap = [
        'leadership' => ['Champion Bold Innovation', 'Cultivate Global Citizens', 'Nurture Lifelong Learning', 'Lead with Purpose', 'Ethical and Inclusive Leadership'],
        'education' => ['Expand Access to Global Opportunities', 'Foster Collaborative Innovation', 'Embrace Inclusivity and Beyond'],
        'emerging' => ['Innovation', 'Strategic and Inclusive Growth', 'Empowerment of Others'],
        'regional' => ['Comprehensive Internationalization Efforts', 'Cooperation and Collaboration', 'Measurable Impact'],
        'global' => ['Ignite Intercultural Understanding', 'Empower Changemakers', 'Cultivate Active Engagement']
    ];
    
    return $criteriaMap[$awardType] ?? [];
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

// Update award counters and readiness (shared function)
function updateAwardCounters($awardType, $contentType) {
    $countersDbFile = '../data/award_counters.json';
    
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
?> 