<?php
/**
 * Checklist API for Award Management System
 * Handles criteria mapping, checklist tracking, and readiness calculation
 */

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

function load_checklist_db() {
    $dbFile = '../data/checklist.json';
    if (!file_exists($dbFile)) {
        return ['auto_id' => 1, 'mappings' => [], 'manual_overrides' => [], 'criterion_links' => []];
    }
    return json_decode(file_get_contents($dbFile), true);
}

function save_checklist_db($db, $dbFile) {
    return file_put_contents($dbFile, json_encode($db, JSON_PRETTY_PRINT));
}

function getAwardCriteria() {
    return [
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
}

function checkCriterionSatisfaction($content, $criterion) {
    $text = strtolower($content['title'] . ' ' . $content['description'] . ' ' . ($content['ocr_text'] ?? ''));
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

function calculateAwardReadiness($awardType, $satisfiedCriteria, $totalCriteria) {
    $satisfactionRate = count($satisfiedCriteria) / count($totalCriteria);
    
    if ($satisfactionRate >= 1.0) {
        return [
            'status' => 'Ready to Apply',
            'color' => 'green',
            'icon' => '✅',
            'satisfaction_rate' => $satisfactionRate
        ];
    } elseif ($satisfactionRate >= 0.7) {
        return [
            'status' => 'Nearly Ready',
            'color' => 'yellow',
            'icon' => '⚠️',
            'satisfaction_rate' => $satisfactionRate
        ];
    } else {
        return [
            'status' => 'Incomplete',
            'color' => 'red',
            'icon' => '❌',
            'satisfaction_rate' => $satisfactionRate
        ];
    }
}

function generateContentSuggestions($criterion, $awardType) {
    $suggestions = [
        'Champion Bold Innovation' => [
            'Create documents showcasing innovative international programs',
            'Document cutting-edge research collaborations',
            'Showcase pioneering educational initiatives',
            'Record breakthrough technology implementations'
        ],
        'Cultivate Global Citizens' => [
            'Document student exchange programs',
            'Record cultural immersion activities',
            'Showcase global citizenship education initiatives',
            'Document international student success stories'
        ],
        'Nurture Lifelong Learning' => [
            'Document continuing education programs',
            'Record professional development opportunities',
            'Showcase alumni engagement activities',
            'Document skill development initiatives'
        ],
        'Lead with Purpose' => [
            'Document strategic planning initiatives',
            'Record vision statements and mission alignment',
            'Showcase leadership development programs',
            'Document organizational transformation efforts'
        ],
        'Ethical and Inclusive Leadership' => [
            'Document diversity and inclusion programs',
            'Record ethical guidelines and policies',
            'Showcase inclusive policy implementations',
            'Document equity-focused initiatives'
        ],
        'Expand Access to Global Opportunities' => [
            'Document scholarship programs',
            'Record international partnerships',
            'Showcase accessibility initiatives',
            'Document global opportunity expansion efforts'
        ],
        'Foster Collaborative Innovation' => [
            'Document joint research projects',
            'Record international collaborations',
            'Showcase innovative program partnerships',
            'Document cross-institutional initiatives'
        ],
        'Embrace Inclusivity and Beyond' => [
            'Document inclusive practices',
            'Record diversity initiatives',
            'Showcase equity-focused programs',
            'Document inclusive policy implementations'
        ],
        'Innovation' => [
            'Document new approaches and methodologies',
            'Record creative solutions to challenges',
            'Showcase breakthrough initiatives',
            'Document innovative program designs'
        ],
        'Strategic and Inclusive Growth' => [
            'Document growth strategies and plans',
            'Record expansion initiatives',
            'Showcase inclusive development programs',
            'Document strategic partnerships'
        ],
        'Empowerment of Others' => [
            'Document mentoring programs',
            'Record capacity building initiatives',
            'Showcase empowerment-focused activities',
            'Document leadership development efforts'
        ],
        'Comprehensive Internationalization Efforts' => [
            'Document holistic internationalization strategies',
            'Record comprehensive program portfolios',
            'Showcase integrated approaches',
            'Document systematic internationalization plans'
        ],
        'Cooperation and Collaboration' => [
            'Document partnership agreements',
            'Record collaborative projects',
            'Showcase cooperative initiatives',
            'Document joint ventures and alliances'
        ],
        'Measurable Impact' => [
            'Document outcomes and results',
            'Record metrics and KPIs',
            'Showcase success stories',
            'Document quantifiable achievements'
        ],
        'Ignite Intercultural Understanding' => [
            'Document cultural exchange programs',
            'Record intercultural dialogue initiatives',
            'Showcase cultural awareness activities',
            'Document cross-cultural learning experiences'
        ],
        'Empower Changemakers' => [
            'Document leadership development programs',
            'Record change initiatives',
            'Showcase empowerment-focused activities',
            'Document social impact projects'
        ],
        'Cultivate Active Engagement' => [
            'Document community engagement programs',
            'Record participatory initiatives',
            'Showcase active involvement activities',
            'Document civic engagement efforts'
        ]
    ];
    
    return $suggestions[$criterion] ?? ["Create content that demonstrates {$criterion}"];
}

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $db = load_checklist_db();
    $dbFile = '../data/checklist.json';

    if ($action === 'get_award_checklist') {
        $awardType = $_GET['award_type'] ?? '';
        if (empty($awardType)) {
            respond(false, ['message' => 'Award type required']);
        }
        
        // Get documents and events for this award
        $documentsDb = json_decode(file_get_contents('../data/documents.json'), true);
        $eventsDb = json_decode(file_get_contents('../data/events.json'), true);
        
        $documents = array_filter($documentsDb['documents'], function($doc) use ($awardType) {
            return $doc['status'] === 'Active' && $doc['award_type'] === $awardType;
        });
        
        $events = array_filter($eventsDb['events'], function($event) use ($awardType) {
            return $event['status'] === 'Active' && $event['award_type'] === $awardType;
        });
        
        // Combine all content
        $allContent = array_merge(
            array_map(function($doc) {
                return [
                    'id' => 'doc_' . $doc['id'],
                    'title' => $doc['document_name'],
                    'description' => $doc['description'] ?? '',
                    'ocr_text' => $doc['ocr_text'] ?? '',
                    'type' => 'document',
                    'date' => $doc['upload_date']
                ];
            }, $documents),
            array_map(function($event) {
                return [
                    'id' => 'event_' . $event['id'],
                    'title' => $event['title'],
                    'description' => $event['description'] ?? '',
                    'ocr_text' => $event['ocr_text'] ?? '',
                    'type' => 'event',
                    'date' => $event['created_date']
                ];
            }, $events)
        );
        
        // Get criteria for this award
        $criteria = getAwardCriteria()[$awardType] ?? [];
        
        // Analyze each criterion
        $checklist = [];
        $satisfiedCriteria = [];
        $unsatisfiedCriteria = [];
        
        foreach ($criteria as $criterion) {
            $satisfied = false;
            $supportingContent = [];
            
            // Check if any content satisfies this criterion
            foreach ($allContent as $content) {
                if (checkCriterionSatisfaction($content, $criterion)) {
                    $satisfied = true;
                    $supportingContent[] = $content;
                }
            }
            
            // Check for manual overrides
            $overrideKey = $awardType . '_' . md5($criterion);
            if (isset($db['manual_overrides'][$overrideKey])) {
                $satisfied = $db['manual_overrides'][$overrideKey]['satisfied'];
            }
            
            $checklist[] = [
                'criterion' => $criterion,
                'satisfied' => $satisfied,
                'supporting_content' => $supportingContent,
                'suggestions' => $satisfied ? [] : generateContentSuggestions($criterion, $awardType)
            ];
            
            if ($satisfied) {
                $satisfiedCriteria[] = $criterion;
            } else {
                $unsatisfiedCriteria[] = $criterion;
            }
        }
        
        // Calculate readiness
        $readiness = calculateAwardReadiness($awardType, $satisfiedCriteria, $criteria);
        
        respond(true, [
            'award_type' => $awardType,
            'checklist' => $checklist,
            'satisfied_criteria' => $satisfiedCriteria,
            'unsatisfied_criteria' => $unsatisfiedCriteria,
            'readiness' => $readiness,
            'total_content' => count($allContent),
            'document_count' => count($documents),
            'event_count' => count($events)
        ]);
    }

    if ($action === 'get_all_checklists') {
        $awardTypes = array_keys(getAwardCriteria());
        $allChecklists = [];
        
        foreach ($awardTypes as $awardType) {
            $response = file_get_contents("http://localhost/api/checklist.php?action=get_award_checklist&award_type=" . urlencode($awardType));
            $result = json_decode($response, true);
            
            if ($result && $result['success']) {
                $allChecklists[] = $result;
            }
        }
        
        respond(true, ['checklists' => $allChecklists]);
    }

    if ($action === 'update_criterion_status') {
        $awardType = $_POST['award_type'] ?? '';
        $criterion = $_POST['criterion'] ?? '';
        $satisfied = $_POST['satisfied'] ?? false;
        
        if (empty($awardType) || empty($criterion)) {
            respond(false, ['message' => 'Award type and criterion required']);
        }
        
        $overrideKey = $awardType . '_' . md5($criterion);
        $db['manual_overrides'][$overrideKey] = [
            'award_type' => $awardType,
            'criterion' => $criterion,
            'satisfied' => (bool)$satisfied,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        save_checklist_db($db, $dbFile);
        
        respond(true, ['message' => 'Criterion status updated successfully']);
    }

    if ($action === 'get_missing_content_suggestions') {
        $awardType = $_GET['award_type'] ?? '';
        if (empty($awardType)) {
            respond(false, ['message' => 'Award type required']);
        }
        
        // Get checklist for this award
        $response = file_get_contents("http://localhost/api/checklist.php?action=get_award_checklist&award_type=" . urlencode($awardType));
        $result = json_decode($response, true);
        
        if (!$result || !$result['success']) {
            respond(false, ['message' => 'Failed to get checklist']);
        }
        
        $suggestions = [];
        foreach ($result['checklist'] as $item) {
            if (!$item['satisfied']) {
                $suggestions[] = [
                    'criterion' => $item['criterion'],
                    'suggestions' => $item['suggestions'],
                    'priority' => in_array($item['criterion'], [
                        'Champion Bold Innovation',
                        'Expand Access to Global Opportunities',
                        'Innovation',
                        'Comprehensive Internationalization Efforts',
                        'Ignite Intercultural Understanding'
                    ]) ? 'high' : 'medium'
                ];
            }
        }
        
        respond(true, ['suggestions' => $suggestions]);
    }

    if ($action === 'get_readiness_summary') {
        $awardTypes = array_keys(getAwardCriteria());
        $summary = [];
        
        foreach ($awardTypes as $awardType) {
            $response = file_get_contents("http://localhost/api/checklist.php?action=get_award_checklist&award_type=" . urlencode($awardType));
            $result = json_decode($response, true);
            
            if ($result && $result['success']) {
                $summary[] = [
                    'award_type' => $awardType,
                    'readiness' => $result['readiness'],
                    'satisfied_count' => count($result['satisfied_criteria']),
                    'total_count' => count($result['checklist']),
                    'document_count' => $result['document_count'],
                    'event_count' => $result['event_count']
                ];
            }
        }
        
        respond(true, ['summary' => $summary]);
    }

// Removed link_content_to_criterion and unlink_content_from_criterion - no longer needed for automatic system

// Removed get_available_content - no longer needed for automatic system

if ($action === 'analyze_single_content') {
    $contentType = $_POST['content_type'] ?? '';
    $contentId = $_POST['content_id'] ?? '';
    
    if (empty($contentType) || empty($contentId)) {
        respond(false, ['message' => 'Content type and ID required']);
    }
    
    try {
        $analysis = performSingleContentAnalysis($contentType, $contentId);
        
        // Update checklist based on analysis
        updateChecklistFromAnalysis($analysis);
        
        respond(true, ['analysis' => $analysis]);
    } catch (Exception $e) {
        respond(false, ['message' => 'Analysis failed: ' . $e->getMessage()]);
    }
}

if ($action === 'analyze_all_content') {
    try {
        $analysis = performBatchContentAnalysis();
        
        // Update all checklists based on analysis
        updateAllChecklistsFromAnalysis($analysis);
        
        respond(true, ['analysis' => $analysis]);
    } catch (Exception $e) {
        respond(false, ['message' => 'Batch analysis failed: ' . $e->getMessage()]);
    }
}

// Helper function to perform single content analysis
function performSingleContentAnalysis($contentType, $contentId) {
    $analysis = [
        'content_type' => $contentType,
        'content_id' => $contentId,
        'supported_awards' => [],
        'satisfied_criteria' => [],
        'keywords_found' => [],
        'confidence_score' => 0,
        'recommendations' => []
    ];
    
    if ($contentType === 'document') {
        // Get document data
        $documentsDb = json_decode(file_get_contents('../data/documents.json'), true);
        $document = null;
        foreach ($documentsDb['documents'] as $doc) {
            if ($doc['id'] == $contentId && $doc['status'] === 'Active') {
                $document = $doc;
                break;
            }
        }
        
        if (!$document) {
            throw new Exception('Document not found');
        }
        
        // Perform analysis
        $content = $document['document_name'] . ' ' . ($document['description'] ?? '');
        $analysis = performContentAnalysis($content);
        $analysis['content_type'] = 'document';
        $analysis['content_id'] = $contentId;
        
    } elseif ($contentType === 'event') {
        // Get event data
        $eventsDb = json_decode(file_get_contents('../data/events.json'), true);
        $event = null;
        foreach ($eventsDb['events'] as $evt) {
            if ($evt['id'] == $contentId && $evt['status'] === 'Active') {
                $event = $evt;
                break;
            }
        }
        
        if (!$event) {
            throw new Exception('Event not found');
        }
        
        // Perform analysis
        $content = $event['title'] . ' ' . ($event['description'] ?? '');
        $analysis = performContentAnalysis($content);
        $analysis['content_type'] = 'event';
        $analysis['content_id'] = $contentId;
    }
    
    return $analysis;
}

// Helper function to perform content analysis
function performContentAnalysis($content) {
    $analysis = [
        'supported_awards' => [],
        'satisfied_criteria' => [],
        'keywords_found' => [],
        'confidence_score' => 0,
        'recommendations' => []
    ];
    
    // Award keywords and criteria mapping
    $awardKeywords = [
        'leadership' => [
            'keywords' => ['leadership', 'internationalization', 'global', 'strategic', 'vision', 'innovation', 'partnership', 'collaboration', 'exchange', 'program', 'initiative', 'development', 'international', 'cross-cultural', 'cultural', 'diversity', 'inclusion', 'mentorship', 'faculty', 'student', 'research', 'academic', 'institutional', 'governance', 'policy', 'framework', 'strategy', 'planning', 'management', 'administration', 'award', 'recognition', 'excellence', 'achievement', 'impact', 'outcome', 'champion', 'bold', 'innovation', 'cultivate', 'global citizens', 'lifelong learning', 'purpose', 'ethical', 'inclusive leadership'],
            'criteria' => ['Champion Bold Innovation', 'Cultivate Global Citizens', 'Nurture Lifelong Learning', 'Lead with Purpose', 'Ethical and Inclusive Leadership']
        ],
        'education' => [
            'keywords' => ['education', 'program', 'curriculum', 'academic', 'course', 'learning', 'teaching', 'pedagogy', 'instruction', 'training', 'development', 'skill', 'knowledge', 'expertise', 'competency', 'qualification', 'certification', 'degree', 'diploma', 'certificate', 'study', 'research', 'scholarship', 'international', 'global', 'cross-cultural', 'multicultural', 'diverse', 'inclusive', 'accessible', 'opportunity', 'access', 'expand', 'foster', 'collaborative', 'innovation', 'beyond', 'inclusivity'],
            'criteria' => ['Expand Access to Global Opportunities', 'Foster Collaborative Innovation', 'Embrace Inclusivity and Beyond']
        ],
        'emerging' => [
            'keywords' => ['emerging', 'leadership', 'innovation', 'growth', 'development', 'strategic', 'inclusive', 'empowerment', 'mentoring', 'guidance', 'support', 'advancement', 'progress', 'future', 'potential', 'talent', 'young', 'new', 'rising', 'upcoming', 'promising', 'breakthrough', 'pioneering', 'cutting-edge', 'forward-thinking', 'visionary', 'transformative', 'revolutionary', 'groundbreaking'],
            'criteria' => ['Innovation', 'Strategic and Inclusive Growth', 'Empowerment of Others']
        ],
        'regional' => [
            'keywords' => ['regional', 'office', 'internationalization', 'comprehensive', 'efforts', 'cooperation', 'collaboration', 'measurable', 'impact', 'regional', 'local', 'community', 'partnership', 'network', 'coordination', 'integration', 'unified', 'systematic', 'organized', 'structured', 'planned', 'strategic', 'outreach', 'engagement', 'involvement', 'participation', 'contribution', 'service', 'support'],
            'criteria' => ['Comprehensive Internationalization Efforts', 'Cooperation and Collaboration', 'Measurable Impact']
        ],
        'global' => [
            'keywords' => ['global', 'citizenship', 'intercultural', 'understanding', 'changemakers', 'engagement', 'active', 'community', 'social', 'responsibility', 'awareness', 'consciousness', 'empathy', 'tolerance', 'respect', 'diversity', 'inclusion', 'equity', 'justice', 'sustainability', 'environmental', 'humanitarian', 'volunteer', 'service', 'advocacy', 'activism', 'leadership', 'initiative', 'movement', 'change', 'transformation'],
            'criteria' => ['Ignite Intercultural Understanding', 'Empower Changemakers', 'Cultivate Active Engagement']
        ]
    ];
    
    $contentLower = strtolower($content);
    $foundKeywords = [];
    $awardScores = [];
    
    // Analyze content against each award
    foreach ($awardKeywords as $awardType => $awardData) {
        $score = 0;
        $matchedKeywords = [];
        
        foreach ($awardData['keywords'] as $keyword) {
            if (strpos($contentLower, strtolower($keyword)) !== false) {
                $score += 1;
                $matchedKeywords[] = $keyword;
                $foundKeywords[] = $keyword;
            }
        }
        
        if ($score > 0) {
            $confidence = min(100, ($score / count($awardData['keywords'])) * 100);
            $awardScores[$awardType] = $confidence;
            
            $analysis['supported_awards'][] = [
                'award_type' => $awardType,
                'confidence' => round($confidence)
            ];
            
            // Map to satisfied criteria
            foreach ($awardData['criteria'] as $criterion) {
                $analysis['satisfied_criteria'][] = [
                    'award_type' => $awardType,
                    'criterion' => $criterion,
                    'confidence' => round($confidence)
                ];
            }
        }
    }
    
    $analysis['keywords_found'] = array_unique($foundKeywords);
    $analysis['confidence_score'] = !empty($awardScores) ? round(array_sum($awardScores) / count($awardScores)) : 0;
    
    // Generate recommendations
    if (empty($analysis['supported_awards'])) {
        $analysis['recommendations'][] = [
            'title' => 'Content Classification Needed',
            'description' => 'This content may need manual review or additional keywords to be properly classified.'
        ];
    }
    
    return $analysis;
}

// Helper function to perform batch content analysis
function performBatchContentAnalysis() {
    $analysis = [
        'total_documents' => 0,
        'total_events' => 0,
        'total_criteria_satisfied' => 0,
        'awards_ready' => 0,
        'award_breakdown' => [],
        'missing_criteria' => []
    ];
    
    $awardTypes = ['leadership', 'education', 'emerging', 'regional', 'global'];
    
    foreach ($awardTypes as $awardType) {
        $awardAnalysis = [
            'award_type' => $awardType,
            'documents_count' => 0,
            'events_count' => 0,
            'satisfied_criteria' => 0,
            'total_criteria' => 0,
            'readiness' => 'Incomplete'
        ];
        
        // Count documents and events for this award
        $documentsDb = json_decode(file_get_contents('../data/documents.json'), true);
        $eventsDb = json_decode(file_get_contents('../data/events.json'), true);
        
        $documents = array_filter($documentsDb['documents'], function($doc) use ($awardType) {
            return $doc['status'] === 'Active' && $doc['award_type'] === $awardType;
        });
        
        $events = array_filter($eventsDb['events'], function($event) use ($awardType) {
            return $event['status'] === 'Active' && $event['award_type'] === $awardType;
        });
        
        $awardAnalysis['documents_count'] = count($documents);
        $awardAnalysis['events_count'] = count($events);
        $analysis['total_documents'] += count($documents);
        $analysis['total_events'] += count($events);
        
        // Get criteria for this award
        $criteria = getAwardCriteria($awardType);
        $awardAnalysis['total_criteria'] = count($criteria);
        
        // Check satisfied criteria
        $db = load_checklist_db();
        $satisfiedCount = 0;
        foreach ($criteria as $criterion) {
            $linkKey = $awardType . '_' . md5($criterion);
            if (isset($db['criterion_links'][$linkKey]) && !empty($db['criterion_links'][$linkKey])) {
                $satisfiedCount++;
            }
        }
        
        $awardAnalysis['satisfied_criteria'] = $satisfiedCount;
        $analysis['total_criteria_satisfied'] += $satisfiedCount;
        
        // Determine readiness
        if ($satisfiedCount === count($criteria)) {
            $awardAnalysis['readiness'] = 'Ready to Apply';
            $analysis['awards_ready']++;
        } elseif ($satisfiedCount >= count($criteria) * 0.8) {
            $awardAnalysis['readiness'] = 'Nearly Ready';
        }
        
        $analysis['award_breakdown'][] = $awardAnalysis;
        
        // Add missing criteria
        foreach ($criteria as $criterion) {
            $linkKey = $awardType . '_' . md5($criterion);
            if (!isset($db['criterion_links'][$linkKey]) || empty($db['criterion_links'][$linkKey])) {
                $analysis['missing_criteria'][] = [
                    'award_type' => $awardType,
                    'criterion' => $criterion
                ];
            }
        }
    }
    
    return $analysis;
}

// Helper function to update checklist from analysis
function updateChecklistFromAnalysis($analysis) {
    $db = load_checklist_db();
    
    if (!isset($db['criterion_links'])) {
        $db['criterion_links'] = [];
    }
    
    // Link content to satisfied criteria
    foreach ($analysis['satisfied_criteria'] as $criterion) {
        $linkKey = $criterion['award_type'] . '_' . md5($criterion['criterion']);
        $linkId = $analysis['content_type'] . '_' . $analysis['content_id'];
        
        if (!isset($db['criterion_links'][$linkKey])) {
            $db['criterion_links'][$linkKey] = [];
        }
        
        $db['criterion_links'][$linkKey][$linkId] = [
            'award_type' => $criterion['award_type'],
            'criterion' => $criterion['criterion'],
            'content_id' => $analysis['content_id'],
            'content_type' => $analysis['content_type'],
            'linked_at' => date('Y-m-d H:i:s'),
            'confidence' => $criterion['confidence']
        ];
    }
    
    save_checklist_db($db, '../data/checklist.json');
}

// Helper function to update all checklists from batch analysis
function updateAllChecklistsFromAnalysis($analysis) {
    // This function would update all checklists based on the batch analysis
    // For now, we'll just save the analysis results
    $db = load_checklist_db();
    $db['last_batch_analysis'] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'results' => $analysis
    ];
    save_checklist_db($db, '../data/checklist.json');
}

if ($action === 'get_checklist_status') {
    $awardType = $_GET['award_type'] ?? '';
    
    if (empty($awardType)) {
        respond(false, ['message' => 'Award type required']);
    }
    
    $db = load_checklist_db();
    $criteria = getAwardCriteria($awardType);
    $status = [];
    
    foreach ($criteria as $criterion) {
        $linkKey = $awardType . '_' . md5($criterion);
        $isSatisfied = isset($db['criterion_links'][$linkKey]) && !empty($db['criterion_links'][$linkKey]);
        
        $status[] = [
            'criterion' => $criterion,
            'satisfied' => $isSatisfied,
            'linked_content' => $isSatisfied ? array_values($db['criterion_links'][$linkKey]) : []
        ];
    }
    
    respond(true, ['status' => $status]);
}

if ($action === 'get_missing_criteria_report') {
    $db = load_checklist_db();
    $awardTypes = ['leadership', 'education', 'emerging', 'regional', 'global'];
    $report = [];
    
    foreach ($awardTypes as $awardType) {
        $criteria = getAwardCriteria($awardType);
        $missing = [];
        $satisfied = [];
        
        foreach ($criteria as $criterion) {
            $linkKey = $awardType . '_' . md5($criterion);
            $isSatisfied = isset($db['criterion_links'][$linkKey]) && !empty($db['criterion_links'][$linkKey]);
            
            if ($isSatisfied) {
                $satisfied[] = $criterion;
            } else {
                $missing[] = $criterion;
            }
        }
        
        $report[] = [
            'award_type' => $awardType,
            'total_criteria' => count($criteria),
            'satisfied_criteria' => count($satisfied),
            'missing_criteria' => count($missing),
            'readiness' => count($missing) === 0 ? 'Ready to Apply' : 'Incomplete',
            'missing_list' => $missing,
            'satisfied_list' => $satisfied
        ];
    }
    
    respond(true, ['report' => $report]);
}

if ($action === 'get_counter_summary') {
    // Get counter data
    $countersResponse = file_get_contents('../data/award_counters.json');
    $counters = json_decode($countersResponse, true);
    
    if (!$counters) {
        $counters = [
            'leadership' => ['documents' => 0, 'events' => 0, 'threshold' => 5, 'readiness' => 'Incomplete', 'total_content' => 0],
            'education' => ['documents' => 0, 'events' => 0, 'threshold' => 3, 'readiness' => 'Incomplete', 'total_content' => 0],
            'emerging' => ['documents' => 0, 'events' => 0, 'threshold' => 3, 'readiness' => 'Incomplete', 'total_content' => 0],
            'regional' => ['documents' => 0, 'events' => 0, 'threshold' => 4, 'readiness' => 'Incomplete', 'total_content' => 0],
            'global' => ['documents' => 0, 'events' => 0, 'threshold' => 3, 'readiness' => 'Incomplete', 'total_content' => 0]
        ];
    }
    
    $awardNames = [
        'leadership' => 'Internationalization (IZN) Leadership Award',
        'education' => 'Outstanding International Education Program Award',
        'emerging' => 'Emerging Leadership Award',
        'regional' => 'Best Regional Office for Internationalization Award',
        'global' => 'Global Citizenship Award'
    ];
    
    $summary = [];
    $totalReady = 0;
    $totalIncomplete = 0;
    
    foreach ($counters as $awardType => $data) {
        $summary[] = [
            'award_type' => $awardType,
            'award_name' => $awardNames[$awardType],
            'documents_count' => $data['documents'],
            'events_count' => $data['events'],
            'total_content' => $data['total_content'],
            'threshold' => $data['threshold'],
            'readiness' => $data['readiness'],
            'missing_count' => max(0, $data['threshold'] - $data['total_content']),
            'progress_percentage' => min(100, ($data['total_content'] / $data['threshold']) * 100)
        ];
        
        if ($data['readiness'] === 'Ready to Apply') {
            $totalReady++;
        } else {
            $totalIncomplete++;
        }
    }
    
    respond(true, [
        'summary' => $summary,
        'totals' => [
            'total_awards' => count($summary),
            'ready_awards' => $totalReady,
            'incomplete_awards' => $totalIncomplete,
            'total_documents' => array_sum(array_column($summary, 'documents_count')),
            'total_events' => array_sum(array_column($summary, 'events_count')),
            'total_content' => array_sum(array_column($summary, 'total_content'))
        ]
    ]);
}

    respond(false, ['message' => 'Unknown action']);
} catch (Throwable $e) {
    respond(false, ['message' => 'Server error', 'error' => $e->getMessage()]);
}
?>
