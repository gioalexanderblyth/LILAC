<?php
/**
 * Fix the uploaded CHED test file by manually extracting content and updating the database
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get the uploaded file record
    $stmt = $pdo->prepare("SELECT * FROM enhanced_documents WHERE id = 12");
    $stmt->execute();
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        echo "File not found\n";
        exit;
    }
    
    echo "Found file: " . $file['document_name'] . "\n";
    echo "File path: " . $file['file_path'] . "\n";
    
    // Read the file content
    $filePath = $file['file_path'];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        echo "File content length: " . strlen($content) . " characters\n";
        
        // Test categorization
        $categories = testCategorization($content);
        echo "Detected categories: " . implode(', ', $categories) . "\n";
        
        // Update the database record with extracted content
        $updateStmt = $pdo->prepare("
            UPDATE enhanced_documents 
            SET extracted_content = ?, 
                award_assignments = ?,
                analysis_data = ?,
                updated_at = NOW()
            WHERE id = 12
        ");
        
        $awardAssignments = json_encode($categories);
        $analysisData = json_encode([
            'categories' => $categories,
            'content_length' => strlen($content),
            'analysis_date' => date('Y-m-d H:i:s')
        ]);
        
        $updateStmt->execute([$content, $awardAssignments, $analysisData]);
        
        echo "Database updated successfully!\n";
        
        // Test the awards API again
        echo "\nTesting awards API...\n";
        $url = 'http://localhost/LILAC/api/awards.php?action=get_all';
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data && isset($data['counts'])) {
            echo "Award counts: " . json_encode($data['counts']) . "\n";
        }
        
    } else {
        echo "Physical file not found at: $filePath\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}

function testCategorization($text) {
    $text = strtolower($text);
    
    // Keywords for each category (from universal_upload_handler.php)
    $keywords = [
        'docs' => [
            'document', 'file', 'report', 'paper', 'study', 'analysis', 'research',
            'proposal', 'plan', 'strategy', 'policy', 'guideline', 'manual'
        ],
        'award_leadership' => [
            'champion bold innovation', 'cultivate global citizens', 'nurture lifelong learning',
            'lead with purpose', 'ethical and inclusive leadership', 'internationalization',
            'leadership', 'innovation', 'global citizens', 'lifelong learning', 'purpose',
            'ethical', 'inclusive', 'bold', 'champion', 'cultivate', 'nurture'
        ],
        'award_education' => [
            'expand access to global opportunities', 'foster collaborative innovation',
            'embrace inclusivity and beyond', 'international education', 'global opportunities',
            'collaborative innovation', 'inclusivity', 'education program', 'academic',
            'curriculum', 'international', 'global', 'opportunities', 'collaborative'
        ],
        'award_emerging' => [
            'innovation', 'strategic and inclusive growth', 'empowerment of others',
            'emerging leadership', 'strategic growth', 'inclusive growth', 'empowerment',
            'emerging', 'strategic', 'inclusive', 'growth', 'empower', 'mentoring'
        ],
        'award_regional' => [
            'comprehensive internationalization efforts', 'cooperation and collaboration',
            'measurable impact', 'regional office', 'internationalization efforts',
            'cooperation', 'collaboration', 'measurable impact', 'regional', 'office',
            'comprehensive', 'efforts', 'measurable', 'impact'
        ],
        'award_global' => [
            'ignite intercultural understanding', 'empower changemakers',
            'cultivate active engagement', 'global citizenship', 'intercultural understanding',
            'changemakers', 'active engagement', 'citizenship', 'intercultural',
            'understanding', 'changemakers', 'engagement', 'ignite', 'empower', 'cultivate'
        ]
    ];
    
    // Count keyword matches for each category
    $scores = [];
    foreach ($keywords as $category => $categoryKeywords) {
        $score = 0;
        foreach ($categoryKeywords as $keyword) {
            $score += substr_count($text, $keyword);
        }
        $scores[$category] = $score;
    }
    
    // Return all categories with scores > 0, sorted by score
    $detectedCategories = [];
    foreach ($scores as $category => $score) {
        if ($score > 0) {
            $detectedCategories[] = $category;
        }
    }
    
    return $detectedCategories;
}
?>
