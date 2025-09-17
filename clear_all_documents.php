<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "<h2>Clearing all documents...</h2>";
    
    // Get all documents from database
    $stmt = $pdo->query('SELECT id, filename, file_path FROM enhanced_documents');
    $docs = $stmt->fetchAll();
    
    echo "<p>Found " . count($docs) . " documents in database</p>";
    
    // Delete files from file system
    $deletedFiles = 0;
    $deletedFromDB = 0;
    
    foreach ($docs as $doc) {
        $filePath = __DIR__ . '/' . $doc['file_path'];
        
        // Delete from file system
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                $deletedFiles++;
                echo "<p>✓ Deleted file: " . $doc['filename'] . "</p>";
            } else {
                echo "<p>✗ Failed to delete file: " . $doc['filename'] . "</p>";
            }
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM enhanced_documents WHERE id = ?");
        if ($stmt->execute([$doc['id']])) {
            $deletedFromDB++;
        }
    }
    
    echo "<h3>Summary:</h3>";
    echo "<p>Files deleted from file system: " . $deletedFiles . "</p>";
    echo "<p>Records deleted from database: " . $deletedFromDB . "</p>";
    
    // Check final count
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM enhanced_documents');
    $count = $stmt->fetch()['count'];
    echo "<p><strong>Remaining documents in database: " . $count . "</strong></p>";
    
    echo "<p><a href='documents.php'>Go back to Documents page</a></p>";
    
} catch (Exception $e) {
    echo '<p style="color: red;">Error: ' . $e->getMessage() . '</p>';
}
?> 