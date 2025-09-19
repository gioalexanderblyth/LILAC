<?php
require_once 'config/database.php';
$pdo = getDatabase();

// Find the document with original filename "20.pdf"
$stmt = $pdo->prepare("SELECT * FROM enhanced_documents WHERE original_filename = '20.pdf' OR document_name LIKE '%20%'");
$stmt->execute();
$docs = $stmt->fetchAll();

echo "Found " . count($docs) . " documents matching '20':\n";
foreach ($docs as $doc) {
    echo "ID: " . $doc['id'] . "\n";
    echo "Original: " . $doc['original_filename'] . "\n";
    echo "Filename: " . $doc['filename'] . "\n";
    echo "Content: '" . $doc['extracted_content'] . "'\n";
    echo "Category: " . $doc['category'] . "\n";
    echo "---\n";
}
?>
