<?php
/**
 * Sync data between documents and enhanced_documents tables
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "Syncing tables...\n";
    
    // Get the CHED test file from enhanced_documents
    $stmt = $pdo->prepare("SELECT * FROM enhanced_documents WHERE id = 12");
    $stmt->execute();
    $enhancedFile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($enhancedFile) {
        echo "Found enhanced file: " . $enhancedFile['document_name'] . "\n";
        echo "Extracted content length: " . strlen($enhancedFile['extracted_content']) . "\n";
        echo "Award assignments: " . $enhancedFile['award_assignments'] . "\n";
        
        // Update the documents table with the extracted content
        $updateStmt = $pdo->prepare("
            UPDATE documents 
            SET extracted_content = ?, 
                award_assignments = ?,
                updated_at = NOW()
            WHERE id = 12
        ");
        
        $updateStmt->execute([
            $enhancedFile['extracted_content'],
            $enhancedFile['award_assignments']
        ]);
        
        echo "Documents table updated!\n";
        
        // Test the awards API again
        echo "\nTesting awards API...\n";
        $url = 'http://localhost/LILAC/api/awards.php?action=get_all';
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data && isset($data['counts'])) {
            echo "Award counts: " . json_encode($data['counts']) . "\n";
        }
        
        // Test the checklist API
        echo "\nTesting checklist API...\n";
        $url = 'http://localhost/LILAC/api/checklist.php?action=get_readiness_summary';
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data) {
            echo "Checklist response: " . json_encode($data) . "\n";
        } else {
            echo "Checklist API returned empty response\n";
        }
        
    } else {
        echo "Enhanced file not found\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
