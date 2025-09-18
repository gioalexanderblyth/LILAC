<?php
require_once 'config/database.php';
$pdo = getDatabase();

// Find the document with "20" in the name
$stmt = $pdo->prepare("SELECT id, original_filename, filename, extracted_content, category FROM enhanced_documents WHERE original_filename LIKE '%20%' OR document_name LIKE '%20%'");
$stmt->execute();
$docs = $stmt->fetchAll();

if (count($docs) > 0) {
    $doc = $docs[0];
    echo "Found document:\n";
    echo "ID: " . $doc['id'] . "\n";
    echo "Original: " . $doc['original_filename'] . "\n";
    echo "Current content: '" . $doc['extracted_content'] . "'\n";
    echo "Current category: " . $doc['category'] . "\n";
    
    // Update with "Agreement" content and MOU category
    $updateStmt = $pdo->prepare("UPDATE enhanced_documents SET extracted_content = 'Agreement', category = 'MOU' WHERE id = ?");
    $result = $updateStmt->execute([$doc['id']]);
    
    if ($result) {
        echo "✅ Successfully updated document " . $doc['id'] . " with content 'Agreement' and category 'MOU'\n";
    } else {
        echo "❌ Failed to update document\n";
    }
} else {
    echo "No document found with '20' in the name\n";
    
    // Let's see all documents
    $allStmt = $pdo->query("SELECT id, original_filename, document_name, extracted_content FROM enhanced_documents LIMIT 10");
    $allDocs = $allStmt->fetchAll();
    
    echo "All documents:\n";
    foreach ($allDocs as $doc) {
        echo "ID: " . $doc['id'] . " | Original: " . $doc['original_filename'] . " | Name: " . $doc['document_name'] . " | Content: '" . substr($doc['extracted_content'], 0, 50) . "'\n";
    }
}
?>
