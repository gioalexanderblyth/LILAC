<?php
/**
 * Award Analyzer
 * Analyzes documents and assigns them to award categories based on content
 */

class AwardAnalyzer {
    private $pdo;
    
    // CHED Award Criteria (20 criteria total: 5+5+4+3+3)
    private $awardCriteria = [
        'leadership' => [
            'name' => 'International Leadership Award',
            'criteria' => [
                'Champion Bold Innovation',
                'Cultivate Global Citizens', 
                'Nurture Lifelong Learning',
                'Lead with Purpose',
                'Ethical and Inclusive Leadership'
            ],
            'keywords' => ['leadership', 'partnership', 'exchange', 'global', 'international', 'collaboration', 'initiative', 'management', 'coordination']
        ],
        'education' => [
            'name' => 'Outstanding International Education Program',
            'criteria' => [
                'Expand Access to Global Opportunities',
                'Foster Collaborative Innovation',
                'Embrace Inclusivity and Beyond',
                'Drive Academic Excellence',
                'Build Sustainable Partnerships'
            ],
            'keywords' => ['education', 'curriculum', 'research', 'academic', 'program', 'course', 'study', 'learning', 'teaching', 'scholarship']
        ],
        'emerging' => [
            'name' => 'Emerging Leadership Award',
            'criteria' => [
                'Pioneer New Frontiers',
                'Adapt and Transform',
                'Build Capacity',
                'Create Impact'
            ],
            'keywords' => ['emerging', 'innovation', 'new', 'creative', 'pioneering', 'breakthrough', 'advancement', 'development', 'growth', 'future']
        ],
        'regional' => [
            'name' => 'Best Regional Office for International',
            'criteria' => [
                'Comprehensive Internationalization Efforts',
                'Cooperation and Collaboration',
                'Measurable Impact'
            ],
            'keywords' => ['regional', 'local', 'community', 'area', 'district', 'province', 'coordination', 'management', 'office', 'administration']
        ],
        'citizenship' => [
            'name' => 'Global Citizenship Award',
            'criteria' => [
                'Ignite Intercultural Understanding',
                'Empower Changemakers',
                'Cultivate Active Engagement'
            ],
            'keywords' => ['citizenship', 'global', 'cultural', 'exchange', 'community', 'awareness', 'engagement', 'social', 'responsibility', 'diversity']
        ]
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Analyze a document and assign it to award categories
     */
    public function analyze($docId, $extractedContent = null) {
        try {
            // Get document info if not provided
            if ($extractedContent === null) {
                $stmt = $this->pdo->prepare("SELECT * FROM enhanced_documents WHERE id = ?");
                $stmt->execute([$docId]);
                $doc = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$doc) {
                    return ['success' => false, 'message' => 'Document not found'];
                }
                
                // PRIORITY: Always use extracted_content first, even if it's short
                $extractedContent = $doc['extracted_content'] ?? '';
            }
            
            // If content is empty, try fallback methods (but prioritize extracted_content)
            if (empty($extractedContent)) {
                $extractedContent = $this->getFallbackContent($docId);
            }
            
            // Analyze content against award criteria
            $analysis = $this->analyzeContent($extractedContent);
            
            // Update document with analysis results
            $this->updateDocumentAnalysis($docId, $analysis);
            
            // Update award counters
            $this->updateAwardCounters();
            
            return [
                'success' => true,
                'analysis' => $analysis,
                'content_length' => strlen($extractedContent)
            ];
            
        } catch (Exception $e) {
            error_log("Award analysis error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Analyze content against award criteria
     */
    private function analyzeContent($content) {
        $content = strtolower($content);
        $analysis = [
            'matched_awards' => [],
            'satisfied_criteria' => [],
            'confidence_score' => 0,
            'total_keywords_found' => 0
        ];
        
        foreach ($this->awardCriteria as $awardKey => $awardData) {
            $keywordMatches = 0;
            $criteriaMatches = 0;
            
            // Check keyword matches
            foreach ($awardData['keywords'] as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    $keywordMatches++;
                }
            }
            
            // Check criteria matches
            foreach ($awardData['criteria'] as $criterion) {
                if (strpos($content, strtolower($criterion)) !== false) {
                    $criteriaMatches++;
                }
            }
            
            // If we found matches, add to analysis
            if ($keywordMatches > 0 || $criteriaMatches > 0) {
                $analysis['matched_awards'][] = $awardKey;
                $analysis['satisfied_criteria'] = array_merge($analysis['satisfied_criteria'], $awardData['criteria']);
                $analysis['total_keywords_found'] += $keywordMatches;
            }
        }
        
        // Calculate confidence score
        $totalPossibleKeywords = array_sum(array_map(function($award) {
            return count($award['keywords']);
        }, $this->awardCriteria));
        
        $analysis['confidence_score'] = $totalPossibleKeywords > 0 ? 
            round(($analysis['total_keywords_found'] / $totalPossibleKeywords) * 100) : 0;
        
        return $analysis;
    }
    
    /**
     * Get fallback content when extraction fails
     * PRIORITY: extracted_content > document_name > filename > category > description
     */
    private function getFallbackContent($docId) {
        $stmt = $this->pdo->prepare("SELECT document_name, filename, original_filename, category, description, extracted_content FROM enhanced_documents WHERE id = ?");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$doc) {
            return '';
        }
        
        $content = '';
        
        // PRIORITY 1: Use extracted_content if available (even if short)
        if (!empty($doc['extracted_content'])) {
            $content .= $doc['extracted_content'] . ' ';
        }
        
        // PRIORITY 2: Use document name
        if (!empty($doc['document_name'])) {
            $content .= $doc['document_name'] . ' ';
        }
        
        // PRIORITY 3: Use original filename if available
        if (!empty($doc['original_filename'])) {
            $content .= $doc['original_filename'] . ' ';
        }
        
        // PRIORITY 4: Use category
        if (!empty($doc['category'])) {
            $content .= $doc['category'] . ' ';
        }
        
        // PRIORITY 5: Use description
        if (!empty($doc['description'])) {
            $content .= $doc['description'] . ' ';
        }
        
        // PRIORITY 6: Add intelligent keywords based on filename patterns (only as last resort)
        if (empty($doc['extracted_content'])) {
            $filename = $doc['original_filename'] ?: $doc['filename'];
            $content .= $this->generateKeywordsFromFilename($filename);
        }
        
        return trim($content);
    }
    
    /**
     * Generate keywords from filename
     */
    private function generateKeywordsFromFilename($filename) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = str_replace(['_', '-'], ' ', $name);
        $lowerName = strtolower($name);
        
        $keywords = [];
        
        // MOU/MOA related
        if (stripos($lowerName, 'mou') !== false || stripos($lowerName, 'kuma') !== false) {
            $keywords = array_merge($keywords, ['memorandum', 'understanding', 'agreement', 'partnership', 'collaboration', 'international']);
        }
        
        if (stripos($lowerName, 'moa') !== false) {
            $keywords = array_merge($keywords, ['memorandum', 'agreement', 'partnership']);
        }
        
        // General keywords
        if (stripos($lowerName, 'agreement') !== false) {
            $keywords = array_merge($keywords, ['agreement', 'contract', 'partnership']);
        }
        
        if (stripos($lowerName, 'partnership') !== false) {
            $keywords = array_merge($keywords, ['partnership', 'collaboration', 'cooperation']);
        }
        
        if (stripos($lowerName, 'international') !== false) {
            $keywords = array_merge($keywords, ['international', 'global', 'worldwide']);
        }
        
        if (stripos($lowerName, 'education') !== false) {
            $keywords = array_merge($keywords, ['education', 'academic', 'learning', 'teaching']);
        }
        
        if (stripos($lowerName, 'research') !== false) {
            $keywords = array_merge($keywords, ['research', 'study', 'investigation', 'analysis']);
        }
        
        return implode(' ', array_unique($keywords));
    }
    
    /**
     * Update document with analysis results
     */
    private function updateDocumentAnalysis($docId, $analysis) {
        $stmt = $this->pdo->prepare("
            UPDATE enhanced_documents 
            SET 
                award_assignments = ?,
                analysis_data = ?,
                is_analyzed = 1,
                analyzed_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            json_encode($analysis['matched_awards']),
            json_encode($analysis),
            $docId
        ]);
    }
    
    /**
     * Update award counters
     */
    private function updateAwardCounters() {
        // Get counts for each award type
        foreach ($this->awardCriteria as $awardKey => $awardData) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM enhanced_documents 
                WHERE JSON_CONTAINS(award_assignments, ?) AND is_analyzed = 1
            ");
            $stmt->execute([json_encode($awardKey)]);
            $count = $stmt->fetchColumn();
            
            // Update or insert counter
            $stmt = $this->pdo->prepare("
                INSERT INTO award_readiness (award_key, total_documents, satisfied_criteria, readiness_percentage)
                VALUES (?, ?, 0, 0)
                ON DUPLICATE KEY UPDATE 
                    total_documents = VALUES(total_documents)
            ");
            $stmt->execute([$awardKey, $count]);
        }
        
        // Update total counters
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total_uploaded,
                COUNT(CASE WHEN is_analyzed = 1 THEN 1 END) as total_analyzed,
                COUNT(CASE WHEN LENGTH(extracted_content) > 0 THEN 1 END) as total_with_content
            FROM enhanced_documents
        ");
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Store in a summary table or update existing counters
        $stmt = $this->pdo->prepare("
            INSERT INTO document_summary (total_uploaded, total_analyzed, total_with_content)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                total_uploaded = VALUES(total_uploaded),
                total_analyzed = VALUES(total_analyzed),
                total_with_content = VALUES(total_with_content)
        ");
        $stmt->execute([
            $counts['total_uploaded'],
            $counts['total_analyzed'],
            $counts['total_with_content']
        ]);
    }
    
    /**
     * Get analysis summary
     */
    public function getAnalysisSummary() {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total_documents,
                COUNT(CASE WHEN is_analyzed = 1 THEN 1 END) as analyzed_documents,
                COUNT(CASE WHEN LENGTH(extracted_content) > 0 THEN 1 END) as documents_with_content,
                COUNT(CASE WHEN award_assignments IS NOT NULL AND award_assignments != '[]' THEN 1 END) as documents_with_awards
            FROM enhanced_documents
        ");
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
