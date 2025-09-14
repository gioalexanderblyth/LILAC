<?php
/**
 * Award Analysis and Assignment System
 * Analyzes content and automatically assigns documents/events to awards
 */

class AwardAnalyzer {
    private $pdo;
    private $awardTypes;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadAwardTypes();
    }
    
    /**
     * Load award types from database
     */
    private function loadAwardTypes() {
        $stmt = $this->pdo->query("SELECT * FROM award_types WHERE is_active = 1");
        $this->awardTypes = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->awardTypes[$row['award_key']] = [
                'id' => $row['id'],
                'name' => $row['award_name'],
                'criteria' => json_decode($row['criteria'], true),
                'keywords' => json_decode($row['keywords'], true),
                'threshold' => $row['threshold']
            ];
        }
    }
    
    /**
     * Analyze content and determine award assignments
     */
    public function analyzeContent($content, $title = '') {
        $normalizedContent = strtolower($content . ' ' . $title);
        $analysis = [];
        
        foreach ($this->awardTypes as $awardKey => $awardData) {
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
            
            $analysis[$awardKey] = [
                'score' => $score,
                'matchedKeywords' => $matchedKeywords,
                'satisfiedCriteria' => $satisfiedCriteria,
                'confidence' => min($score / 10, 1.0) // Normalize confidence
            ];
        }
        
        return $analysis;
    }
    
    /**
     * Determine award assignments based on analysis
     */
    public function determineAssignments($analysis, $threshold = 0.2) {
        $assignments = [];
        
        foreach ($analysis as $awardKey => $data) {
            if ($data['confidence'] >= $threshold) {
                $assignments[] = [
                    'award_key' => $awardKey,
                    'award_name' => $this->awardTypes[$awardKey]['name'],
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
    
    /**
     * Assign document to awards
     */
    public function assignDocument($documentId, $assignments) {
        // Remove existing assignments
        $stmt = $this->pdo->prepare("DELETE FROM document_award_assignments WHERE document_id = ?");
        $stmt->execute([$documentId]);
        
        // Add new assignments
        foreach ($assignments as $assignment) {
            $stmt = $this->pdo->prepare("
                INSERT INTO document_award_assignments 
                (document_id, award_key, confidence_score, matched_keywords, satisfied_criteria) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $documentId,
                $assignment['award_key'],
                $assignment['confidence'],
                json_encode($assignment['matchedKeywords']),
                json_encode($assignment['satisfiedCriteria'])
            ]);
        }
        
        // Update document with assignments
        $stmt = $this->pdo->prepare("
            UPDATE enhanced_documents 
            SET award_assignments = ?, analysis_data = ? 
            WHERE id = ?
        ");
        
        $stmt->execute([
            json_encode($assignments),
            json_encode($assignments),
            $documentId
        ]);
        
        return true;
    }
    
    /**
     * Assign event to awards
     */
    public function assignEvent($eventId, $assignments) {
        // Remove existing assignments
        $stmt = $this->pdo->prepare("DELETE FROM event_award_assignments WHERE event_id = ?");
        $stmt->execute([$eventId]);
        
        // Add new assignments
        foreach ($assignments as $assignment) {
            $stmt = $this->pdo->prepare("
                INSERT INTO event_award_assignments 
                (event_id, award_key, confidence_score, matched_keywords, satisfied_criteria) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $eventId,
                $assignment['award_key'],
                $assignment['confidence'],
                json_encode($assignment['matchedKeywords']),
                json_encode($assignment['satisfiedCriteria'])
            ]);
        }
        
        // Update event with assignments
        $stmt = $this->pdo->prepare("
            UPDATE enhanced_events 
            SET award_assignments = ?, analysis_data = ? 
            WHERE id = ?
        ");
        
        $stmt->execute([
            json_encode($assignments),
            json_encode($assignments),
            $eventId
        ]);
        
        return true;
    }
    
    /**
     * Calculate readiness status for all awards
     */
    public function calculateReadinessStatus() {
        foreach ($this->awardTypes as $awardKey => $awardData) {
            $this->calculateAwardReadiness($awardKey);
        }
    }
    
    /**
     * Calculate readiness status for a specific award
     */
    private function calculateAwardReadiness($awardKey) {
        // Get all assigned documents and events
        $documents = $this->getAssignedDocuments($awardKey);
        $events = $this->getAssignedEvents($awardKey);
        
        $allItems = array_merge($documents, $events);
        $awardData = $this->awardTypes[$awardKey];
        
        $satisfiedCriteria = [];
        $unsatisfiedCriteria = [];
        
        // Check each criterion
        foreach ($awardData['criteria'] as $criterion) {
            $isSatisfied = false;
            
            foreach ($allItems as $item) {
                $content = strtolower($item['extracted_content'] ?? '');
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
        $totalItems = count($allItems);
        $isReady = $totalItems >= $awardData['threshold'] && $readinessPercentage >= 80;
        
        // Update readiness table
        $stmt = $this->pdo->prepare("
            INSERT INTO award_readiness 
            (award_key, total_documents, total_events, total_items, satisfied_criteria, 
             unsatisfied_criteria, readiness_percentage, is_ready) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            total_documents = VALUES(total_documents),
            total_events = VALUES(total_events),
            total_items = VALUES(total_items),
            satisfied_criteria = VALUES(satisfied_criteria),
            unsatisfied_criteria = VALUES(unsatisfied_criteria),
            readiness_percentage = VALUES(readiness_percentage),
            is_ready = VALUES(is_ready),
            last_calculated = NOW()
        ");
        
        $stmt->execute([
            $awardKey,
            count($documents),
            count($events),
            $totalItems,
            json_encode($satisfiedCriteria),
            json_encode($unsatisfiedCriteria),
            $readinessPercentage,
            $isReady
        ]);
        
        return [
            'isReady' => $isReady,
            'satisfiedCriteria' => $satisfiedCriteria,
            'unsatisfiedCriteria' => $unsatisfiedCriteria,
            'readinessPercentage' => $readinessPercentage,
            'totalItems' => $totalItems,
            'threshold' => $awardData['threshold']
        ];
    }
    
    /**
     * Get documents assigned to an award
     */
    private function getAssignedDocuments($awardKey) {
        $stmt = $this->pdo->prepare("
            SELECT d.* FROM enhanced_documents d
            INNER JOIN document_award_assignments daa ON d.id = daa.document_id
            WHERE daa.award_key = ? AND daa.is_manual_override = 0
        ");
        $stmt->execute([$awardKey]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get events assigned to an award
     */
    private function getAssignedEvents($awardKey) {
        $stmt = $this->pdo->prepare("
            SELECT e.* FROM enhanced_events e
            INNER JOIN event_award_assignments eaa ON e.id = eaa.event_id
            WHERE eaa.award_key = ? AND eaa.is_manual_override = 0
        ");
        $stmt->execute([$awardKey]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get comprehensive status report
     */
    public function getStatusReport() {
        $this->calculateReadinessStatus();
        
        $report = [
            'summary' => [
                'totalDocuments' => 0,
                'totalEvents' => 0,
                'totalItems' => 0,
                'readyAwards' => 0,
                'totalAwards' => count($this->awardTypes)
            ],
            'awards' => [],
            'recommendations' => []
        ];
        
        // Get readiness data
        $stmt = $this->pdo->query("SELECT * FROM award_readiness");
        $readinessData = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $readinessData[$row['award_key']] = $row;
        }
        
        foreach ($this->awardTypes as $awardKey => $awardData) {
            $readiness = $readinessData[$awardKey] ?? null;
            
            if ($readiness) {
                $report['summary']['totalDocuments'] += $readiness['total_documents'];
                $report['summary']['totalEvents'] += $readiness['total_events'];
                $report['summary']['totalItems'] += $readiness['total_items'];
                
                if ($readiness['is_ready']) {
                    $report['summary']['readyAwards']++;
                }
            }
            
            $report['awards'][$awardKey] = [
                'name' => $awardData['name'],
                'criteria' => $awardData['criteria'],
                'threshold' => $awardData['threshold'],
                'readiness' => $readiness ? [
                    'isReady' => (bool)$readiness['is_ready'],
                    'satisfiedCriteria' => json_decode($readiness['satisfied_criteria'], true),
                    'unsatisfiedCriteria' => json_decode($readiness['unsatisfied_criteria'], true),
                    'readinessPercentage' => (float)$readiness['readiness_percentage'],
                    'totalItems' => $readiness['total_items']
                ] : null
            ];
            
            // Generate recommendations for non-ready awards
            if (!$readiness || !$readiness['is_ready']) {
                $recommendations = $this->generateRecommendations($awardKey, $readiness);
                $report['recommendations'] = array_merge($report['recommendations'], $recommendations);
            }
        }
        
        return $report;
    }
    
    /**
     * Generate recommendations for missing content
     */
    private function generateRecommendations($awardKey, $readiness) {
        $recommendations = [];
        $awardData = $this->awardTypes[$awardKey];
        
        if (!$readiness) {
            $readiness = [
                'total_items' => 0,
                'unsatisfied_criteria' => json_encode($awardData['criteria'])
            ];
        }
        
        // Check if threshold is met
        if ($readiness['total_items'] < $awardData['threshold']) {
            $recommendations[] = [
                'type' => 'quantity',
                'awardType' => $awardKey,
                'awardName' => $awardData['name'],
                'message' => "Need " . ($awardData['threshold'] - $readiness['total_items']) . " more document(s) or event(s) to meet minimum threshold",
                'priority' => 'high'
            ];
        }
        
        // Check unsatisfied criteria
        $unsatisfiedCriteria = json_decode($readiness['unsatisfied_criteria'], true) ?? [];
        foreach ($unsatisfiedCriteria as $criterion) {
            $recommendations[] = [
                'type' => 'criteria',
                'awardType' => $awardKey,
                'awardName' => $awardData['name'],
                'criterion' => $criterion,
                'message' => "Missing content demonstrating: " . $criterion,
                'suggestion' => $this->generateContentSuggestion($criterion),
                'priority' => 'medium'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Generate content suggestions for missing criteria
     */
    private function generateContentSuggestion($criterion) {
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
    
    /**
     * Manual override for document assignment
     */
    public function manualOverrideDocument($documentId, $awardKey, $action = 'add') {
        if ($action === 'add') {
            $stmt = $this->pdo->prepare("
                INSERT INTO document_award_assignments 
                (document_id, award_key, confidence_score, matched_keywords, satisfied_criteria, is_manual_override) 
                VALUES (?, ?, 1.0, '[]', '[]', 1)
                ON DUPLICATE KEY UPDATE is_manual_override = 1
            ");
            $stmt->execute([$documentId, $awardKey]);
        } else {
            $stmt = $this->pdo->prepare("
                DELETE FROM document_award_assignments 
                WHERE document_id = ? AND award_key = ?
            ");
            $stmt->execute([$documentId, $awardKey]);
        }
        
        $this->calculateReadinessStatus();
        return true;
    }
    
    /**
     * Manual override for event assignment
     */
    public function manualOverrideEvent($eventId, $awardKey, $action = 'add') {
        if ($action === 'add') {
            $stmt = $this->pdo->prepare("
                INSERT INTO event_award_assignments 
                (event_id, award_key, confidence_score, matched_keywords, satisfied_criteria, is_manual_override) 
                VALUES (?, ?, 1.0, '[]', '[]', 1)
                ON DUPLICATE KEY UPDATE is_manual_override = 1
            ");
            $stmt->execute([$eventId, $awardKey]);
        } else {
            $stmt = $this->pdo->prepare("
                DELETE FROM event_award_assignments 
                WHERE event_id = ? AND award_key = ?
            ");
            $stmt->execute([$eventId, $awardKey]);
        }
        
        $this->calculateReadinessStatus();
        return true;
    }
}
?>
