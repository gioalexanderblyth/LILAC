<?php
/**
 * Manually update document counters for awards match analysis
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "Updating document counters...\n";
    
    // Ensure award_readiness table exists
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
    $defaultAwards = ['leadership', 'education', 'emerging', 'regional', 'citizenship'];
    foreach ($defaultAwards as $award) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO award_readiness (award_key) VALUES (?)");
        $stmt->execute([$award]);
    }
    
    // Reset all counters
    $pdo->exec("UPDATE award_readiness SET 
        total_documents = 0, 
        total_events = 0, 
        total_items = 0,
        readiness_percentage = 0,
        is_ready = 0");
    
    // Get all documents from enhanced_documents table
    $stmt = $pdo->query("SELECT id, document_name, filename, category, extracted_content FROM enhanced_documents");
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($docs) . " documents to analyze\n";
    
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
    
    $analysisResults = [];
    
    foreach ($docs as $doc) {
        $content = strtolower($doc['document_name'] . ' ' . $doc['filename'] . ' ' . $doc['category'] . ' ' . ($doc['extracted_content'] ?? ''));
        
        echo "Analyzing: " . $doc['document_name'] . "\n";
        
        // Check each award type
        foreach ($criteria as $awardType => $keywords) {
            $satisfied = [];
            foreach ($keywords as $keyword) {
                if (strpos($content, strtolower($keyword)) !== false) {
                    $satisfied[] = $keyword;
                }
            }
            
            if (!empty($satisfied)) {
                echo "  - Matches $awardType: " . implode(', ', $satisfied) . "\n";
                
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
                    'satisfied_criteria' => $satisfied
                ];
            }
        }
    }
    
    // Update readiness percentages
    $stmt = $pdo->query("SELECT award_key, total_items FROM award_readiness");
    $awards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($awards as $award) {
        $threshold = 5; // Default threshold
        $readinessPercentage = min(100, ($award['total_items'] / $threshold) * 100);
        $isReady = $award['total_items'] >= $threshold;
        
        $stmt = $pdo->prepare("UPDATE award_readiness SET 
            readiness_percentage = ?,
            is_ready = ?
            WHERE award_key = ?");
        $stmt->execute([$readinessPercentage, $isReady ? 1 : 0, $award['award_key']]);
    }
    
    echo "\nAnalysis completed!\n";
    echo "Results: " . json_encode($analysisResults, JSON_PRETTY_PRINT) . "\n";
    
    // Test the checklist API
    echo "\nTesting checklist API...\n";
    $url = 'http://localhost/LILAC/api/checklist.php?action=get_readiness_summary';
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data && isset($data['summary'])) {
        echo "Checklist API Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Checklist API returned: " . $response . "\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
