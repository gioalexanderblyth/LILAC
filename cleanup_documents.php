<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "Cleaning up duplicate documents...\n";
    
    // First, let's see what's in the database
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM enhanced_documents');
    $totalCount = $stmt->fetch()['count'];
    echo "Total documents in database: " . $totalCount . "\n";
    
    // Find duplicate filenames
    $stmt = $pdo->query('SELECT filename, COUNT(*) as count FROM enhanced_documents GROUP BY filename HAVING COUNT(*) > 1');
    $duplicates = $stmt->fetchAll();
    
    echo "Found " . count($duplicates) . " duplicate filenames\n";
    
    // Remove duplicates, keeping only the most recent one
    foreach ($duplicates as $duplicate) {
        $filename = $duplicate['filename'];
        echo "Removing duplicates for: " . $filename . "\n";
        
        // Keep the most recent entry, delete the rest
        $stmt = $pdo->prepare("DELETE FROM enhanced_documents WHERE filename = ? AND id NOT IN (SELECT * FROM (SELECT MAX(id) FROM enhanced_documents WHERE filename = ?) AS temp)");
        $stmt->execute([$filename, $filename]);
        $deleted = $stmt->rowCount();
        echo "Deleted " . $deleted . " duplicate entries\n";
    }
    
    // Check final count
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM enhanced_documents');
    $finalCount = $stmt->fetch()['count'];
    echo "Final documents count: " . $finalCount . "\n";
    
    // Show remaining documents
    $stmt = $pdo->query('SELECT id, document_name, filename, file_size, upload_date FROM enhanced_documents ORDER BY upload_date DESC');
    $docs = $stmt->fetchAll();
    
    echo "\nRemaining documents:\n";
    foreach ($docs as $doc) {
        echo "- ID: " . $doc['id'] . ", Name: " . $doc['document_name'] . ", File: " . $doc['filename'] . ", Size: " . $doc['file_size'] . " bytes, Date: " . $doc['upload_date'] . "\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?> 