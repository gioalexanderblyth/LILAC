<?php
require_once 'config/database.php';

$db = new Database();
$pdo = $db->getConnection();

// Get the uploaded file
$stmt = $pdo->prepare("SELECT * FROM enhanced_documents WHERE id = 16");
$stmt->execute();
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if ($file) {
    echo "Found file: " . $file['document_name'] . "\n";
    
    // Read the actual file content
    $filePath = $file['file_path'];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        echo "File content length: " . strlen($content) . " characters\n";
        
        // Update the database with extracted content
        $updateStmt = $pdo->prepare("
            UPDATE enhanced_documents 
            SET extracted_content = ?, 
                updated_at = NOW()
            WHERE id = 16
        ");
        
        $updateStmt->execute([$content]);
        echo "Database updated with content!\n";
        
        // Now trigger the counter update
        require_once 'api/documents.php';
        updateAwardReadinessCounters($pdo);
        echo "Counters updated!\n";
        
    } else {
        echo "Physical file not found at: $filePath\n";
    }
} else {
    echo "File not found in database\n";
}
?>
