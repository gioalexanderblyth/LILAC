<?php
require_once 'config/database.php';
$pdo = getDatabase();

// Find document with "20" in the name
$stmt = $pdo->prepare("SELECT id, original_filename, filename, extracted_content FROM enhanced_documents WHERE original_filename LIKE '%20%' OR document_name LIKE '%20%'");
$stmt->execute();
$docs = $stmt->fetchAll();

if (count($docs) > 0) {
    $doc = $docs[0];
    echo "Found document ID: " . $doc['id'] . "\n";
    echo "Original filename: " . $doc['original_filename'] . "\n";
    echo "Current content: '" . $doc['extracted_content'] . "'\n";
    
    // Update with "Agreement" content
    $updateStmt = $pdo->prepare("UPDATE enhanced_documents SET extracted_content = 'Agreement', category = 'MOU' WHERE id = ?");
    $updateStmt->execute([$doc['id']]);
    
    echo "Updated document " . $doc['id'] . " with content 'Agreement' and category 'MOU'\n";
} else {
    echo "No document found with '20' in the name\n";
}
?>
