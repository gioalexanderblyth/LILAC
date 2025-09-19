<?php
/**
 * Intelligent Award Readiness Analyzer
 * Uses context-aware analysis instead of simple string matching
 */

class IntelligentAwardAnalyzer {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Analyze document content for award readiness using intelligent matching
     */
    public function analyzeDocument($documentId, $content, $documentName = '', $category = '') {
        $fullText = strtolower($documentName . ' ' . $category . ' ' . $content);
        
        $analysis = [
            'leadership' => $this->analyzeLeadership($fullText),
            'education' => $this->analyzeEducation($fullText),
            'emerging' => $this->analyzeEmerging($fullText),
            'regional' => $this->analyzeRegional($fullText),
            'citizenship' => $this->analyzeCitizenship($fullText)
        ];
        
        return $analysis;
    }
    
    /**
     * Analyze for Leadership award criteria
     */
    private function analyzeLeadership($text) {
        $criteria = [
            'innovation' => [
                'keywords' => ['innovation', 'innovative', 'breakthrough', 'pioneer', 'trailblaz', 'cutting-edge', 'advanced', 'novel'],
                'phrases' => ['champion bold innovation', 'innovative approach', 'breakthrough technology', 'pioneering work'],
                'context' => ['research', 'development', 'technology', 'method', 'solution', 'approach']
            ],
            'excellence' => [
                'keywords' => ['excellence', 'excellent', 'outstanding', 'superior', 'exceptional', 'distinguished'],
                'phrases' => ['academic excellence', 'excellence in', 'outstanding achievement', 'exceptional performance'],
                'context' => ['performance', 'achievement', 'academic', 'professional', 'service']
            ],
            'vision' => [
                'keywords' => ['vision', 'visionary', 'strategic', 'foresight', 'future-oriented', 'forward-thinking'],
                'phrases' => ['strategic vision', 'future vision', 'visionary leadership', 'strategic planning'],
                'context' => ['leadership', 'planning', 'direction', 'future', 'strategy']
            ],
            'impact' => [
                'keywords' => ['impact', 'influence', 'transformation', 'change', 'improvement', 'enhancement'],
                'phrases' => ['positive impact', 'significant impact', 'transformative impact', 'lasting impact'],
                'context' => ['community', 'society', 'organization', 'institution', 'students']
            ],
            'collaboration' => [
                'keywords' => ['collaboration', 'partnership', 'cooperation', 'teamwork', 'alliance', 'network'],
                'phrases' => ['collaborative effort', 'strategic partnership', 'international collaboration'],
                'context' => ['international', 'interdisciplinary', 'cross-cultural', 'joint', 'shared']
            ]
        ];
        
        return $this->scoreCriteria($text, $criteria);
    }
    
    /**
     * Analyze for Education award criteria
     */
    private function analyzeEducation($text) {
        $criteria = [
            'teaching' => [
                'keywords' => ['teaching', 'instruction', 'pedagogy', 'education', 'learning', 'curriculum'],
                'phrases' => ['excellence in teaching', 'innovative teaching', 'student learning', 'educational impact'],
                'context' => ['students', 'classroom', 'course', 'program', 'academic']
            ],
            'research' => [
                'keywords' => ['research', 'scholarship', 'investigation', 'study', 'analysis', 'publication'],
                'phrases' => ['research excellence', 'scholarly work', 'research contribution', 'academic research'],
                'context' => ['publication', 'journal', 'conference', 'grant', 'funding']
            ],
            'mentoring' => [
                'keywords' => ['mentoring', 'mentorship', 'guidance', 'advising', 'supervision', 'development'],
                'phrases' => ['student mentoring', 'mentorship program', 'academic advising', 'student development'],
                'context' => ['students', 'graduate', 'undergraduate', 'career', 'professional']
            ],
            'curriculum' => [
                'keywords' => ['curriculum', 'program', 'course', 'syllabus', 'instruction', 'pedagogy'],
                'phrases' => ['curriculum development', 'program design', 'course innovation', 'academic program'],
                'context' => ['academic', 'degree', 'certificate', 'program', 'department']
            ],
            'assessment' => [
                'keywords' => ['assessment', 'evaluation', 'measurement', 'outcome', 'achievement', 'performance'],
                'phrases' => ['learning outcomes', 'student assessment', 'academic evaluation', 'performance measurement'],
                'context' => ['students', 'learning', 'academic', 'outcome', 'achievement']
            ]
        ];
        
        return $this->scoreCriteria($text, $criteria);
    }
    
    /**
     * Analyze for Emerging award criteria
     */
    private function analyzeEmerging($text) {
        $criteria = [
            'potential' => [
                'keywords' => ['potential', 'promising', 'emerging', 'rising', 'developing', 'growing'],
                'phrases' => ['emerging leader', 'promising talent', 'rising star', 'developing potential'],
                'context' => ['career', 'professional', 'academic', 'leadership', 'future']
            ],
            'initiative' => [
                'keywords' => ['initiative', 'proactive', 'self-directed', 'independent', 'autonomous', 'self-motivated'],
                'phrases' => ['took initiative', 'self-directed learning', 'independent research', 'proactive approach'],
                'context' => ['project', 'research', 'learning', 'development', 'improvement']
            ],
            'adaptability' => [
                'keywords' => ['adaptable', 'flexible', 'versatile', 'resilient', 'adaptive', 'adjustable'],
                'phrases' => ['adaptable approach', 'flexible thinking', 'resilient performance', 'adaptive learning'],
                'context' => ['change', 'challenge', 'environment', 'situation', 'circumstance']
            ],
            'growth' => [
                'keywords' => ['growth', 'development', 'improvement', 'progress', 'advancement', 'enhancement'],
                'phrases' => ['significant growth', 'continuous improvement', 'rapid development', 'steady progress'],
                'context' => ['skills', 'knowledge', 'performance', 'capability', 'competency']
            ],
            'contribution' => [
                'keywords' => ['contribution', 'contribute', 'input', 'participation', 'involvement', 'engagement'],
                'phrases' => ['valuable contribution', 'significant input', 'active participation', 'meaningful engagement'],
                'context' => ['team', 'project', 'community', 'organization', 'institution']
            ]
        ];
        
        return $this->scoreCriteria($text, $criteria);
    }
    
    /**
     * Analyze for Regional award criteria
     */
    private function analyzeRegional($text) {
        $criteria = [
            'regional_impact' => [
                'keywords' => ['regional', 'local', 'community', 'area', 'district', 'province'],
                'phrases' => ['regional impact', 'community service', 'local development', 'regional initiative'],
                'context' => ['development', 'service', 'outreach', 'engagement', 'partnership']
            ],
            'cultural' => [
                'keywords' => ['cultural', 'heritage', 'tradition', 'diversity', 'multicultural', 'indigenous'],
                'phrases' => ['cultural preservation', 'heritage conservation', 'cultural diversity', 'traditional knowledge'],
                'context' => ['community', 'society', 'preservation', 'promotion', 'education']
            ],
            'economic' => [
                'keywords' => ['economic', 'development', 'growth', 'business', 'entrepreneur', 'commerce'],
                'phrases' => ['economic development', 'business growth', 'entrepreneurial spirit', 'economic impact'],
                'context' => ['community', 'region', 'local', 'business', 'industry']
            ],
            'environmental' => [
                'keywords' => ['environmental', 'sustainability', 'conservation', 'ecology', 'green', 'climate'],
                'phrases' => ['environmental conservation', 'sustainable development', 'green initiative', 'climate action'],
                'context' => ['protection', 'conservation', 'sustainability', 'environment', 'future']
            ],
            'social' => [
                'keywords' => ['social', 'welfare', 'wellbeing', 'health', 'education', 'equity'],
                'phrases' => ['social welfare', 'community wellbeing', 'social equity', 'public health'],
                'context' => ['community', 'public', 'social', 'welfare', 'improvement']
            ]
        ];
        
        return $this->scoreCriteria($text, $criteria);
    }
    
    /**
     * Analyze for Citizenship award criteria
     */
    private function analyzeCitizenship($text) {
        $criteria = [
            'service' => [
                'keywords' => ['service', 'volunteer', 'volunteering', 'community', 'public', 'civic'],
                'phrases' => ['community service', 'volunteer work', 'public service', 'civic engagement'],
                'context' => ['community', 'public', 'volunteer', 'service', 'engagement']
            ],
            'engagement' => [
                'keywords' => ['engagement', 'participation', 'involvement', 'active', 'commitment', 'dedication'],
                'phrases' => ['active engagement', 'community participation', 'civic involvement', 'public engagement'],
                'context' => ['community', 'civic', 'public', 'social', 'local']
            ],
            'leadership' => [
                'keywords' => ['leadership', 'leader', 'leading', 'initiative', 'organize', 'coordinate'],
                'phrases' => ['community leadership', 'civic leadership', 'leadership initiative', 'community organizer'],
                'context' => ['community', 'civic', 'social', 'public', 'organization']
            ],
            'advocacy' => [
                'keywords' => ['advocacy', 'advocate', 'champion', 'support', 'promote', 'defend'],
                'phrases' => ['community advocacy', 'social advocacy', 'advocate for', 'champion of'],
                'context' => ['rights', 'justice', 'equality', 'social', 'community']
            ],
            'responsibility' => [
                'keywords' => ['responsibility', 'accountable', 'ethical', 'integrity', 'honesty', 'trustworthy'],
                'phrases' => ['social responsibility', 'ethical behavior', 'integrity in', 'responsible citizenship'],
                'context' => ['ethical', 'moral', 'social', 'civic', 'responsibility']
            ]
        ];
        
        return $this->scoreCriteria($text, $criteria);
    }
    
    /**
     * Score criteria using intelligent matching
     */
    private function scoreCriteria($text, $criteria) {
        $totalScore = 0;
        $maxScore = count($criteria) * 100; // Each criterion worth up to 100 points
        $satisfiedCriteria = [];
        
        foreach ($criteria as $criterionName => $criterion) {
            $score = 0;
            
            // Check for exact phrases (highest weight)
            foreach ($criterion['phrases'] as $phrase) {
                if (strpos($text, strtolower($phrase)) !== false) {
                    $score += 40;
                    break; // Only count once per criterion
                }
            }
            
            // Check for keywords
            $keywordMatches = 0;
            foreach ($criterion['keywords'] as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $keywordMatches++;
                }
            }
            $score += min(30, $keywordMatches * 8); // Up to 30 points for keywords
            
            // Check for contextual words (medium weight)
            $contextMatches = 0;
            foreach ($criterion['context'] as $context) {
                if (strpos($text, $context) !== false) {
                    $contextMatches++;
                }
            }
            $score += min(30, $contextMatches * 6); // Up to 30 points for context
            
            $totalScore += min(100, $score);
            
            if ($score >= 20) { // Threshold for considering criterion satisfied
                $satisfiedCriteria[] = $criterionName;
            }
        }
        
        return [
            'score' => round(($totalScore / $maxScore) * 100, 2),
            'satisfied_criteria' => $satisfiedCriteria,
            'total_criteria' => count($criteria),
            'is_qualified' => count($satisfiedCriteria) >= 3 // Need at least 3 criteria satisfied
        ];
    }
    
    /**
     * Update award readiness counters using intelligent analysis
     */
    public function updateAwardReadinessCounters() {
        // Ensure award_readiness table exists
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS award_readiness (
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
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO award_readiness (award_key) VALUES (?)");
            $stmt->execute([$award]);
        }
        
        // Reset all counters
        $this->pdo->exec("UPDATE award_readiness SET 
            total_documents = 0, 
            total_events = 0, 
            total_items = 0,
            readiness_percentage = 0,
            is_ready = 0");
        
        // Get all documents from enhanced_documents table
        $stmt = $this->pdo->query("SELECT id, document_name, filename, category, extracted_content FROM enhanced_documents WHERE is_readable = 1");
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $awardCounts = [
            'leadership' => 0,
            'education' => 0,
            'emerging' => 0,
            'regional' => 0,
            'citizenship' => 0
        ];
        
        foreach ($docs as $doc) {
            $analysis = $this->analyzeDocument($doc['id'], $doc['extracted_content'], $doc['document_name'], $doc['category']);
            
            foreach ($analysis as $awardType => $result) {
                if ($result['is_qualified']) {
                    $awardCounts[$awardType]++;
                }
            }
        }
        
        // Update database with intelligent counts
        foreach ($awardCounts as $awardType => $count) {
            $threshold = $this->getAwardThreshold($awardType);
            $readinessPercentage = min(100, ($count / $threshold) * 100);
            $isReady = $count >= $threshold;
            
            $stmt = $this->pdo->prepare("UPDATE award_readiness SET 
                total_documents = ?,
                total_items = ?,
                readiness_percentage = ?,
                is_ready = ?,
                last_calculated = NOW()
                WHERE award_key = ?");
            $stmt->execute([$count, $count, $readinessPercentage, $isReady ? 1 : 0, $awardType]);
        }
    }
    
    /**
     * Get threshold for each award type
     */
    private function getAwardThreshold($awardType) {
        $thresholds = [
            'leadership' => 8,
            'education' => 6,
            'emerging' => 5,
            'regional' => 6,
            'citizenship' => 5
        ];
        
        return $thresholds[$awardType] ?? 5;
    }
}
?>
