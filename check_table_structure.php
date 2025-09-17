<?php
/**
 * Check the structure of both documents tables
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "Checking table structures...\n\n";
    
    // Check documents table structure
    echo "=== DOCUMENTS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE documents");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
    
    echo "\n=== ENHANCED_DOCUMENTS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE enhanced_documents");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
    
    echo "\n=== DOCUMENTS TABLE DATA ===\n";
    $stmt = $pdo->query("SELECT id, document_name, category, ocr_text FROM documents WHERE id = 12");
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($file) {
        echo "ID: " . $file['id'] . "\n";
        echo "Name: " . $file['document_name'] . "\n";
        echo "Category: " . $file['category'] . "\n";
        echo "OCR Text Length: " . strlen($file['ocr_text']) . "\n";
    } else {
        echo "File not found in documents table\n";
    }
    
    echo "\n=== ENHANCED_DOCUMENTS TABLE DATA ===\n";
    $stmt = $pdo->query("SELECT id, document_name, category, extracted_content, award_assignments FROM enhanced_documents WHERE id = 12");
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($file) {
        echo "ID: " . $file['id'] . "\n";
        echo "Name: " . $file['document_name'] . "\n";
        echo "Category: " . $file['category'] . "\n";
        echo "Extracted Content Length: " . strlen($file['extracted_content']) . "\n";
        echo "Award Assignments: " . $file['award_assignments'] . "\n";
    } else {
        echo "File not found in enhanced_documents table\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
