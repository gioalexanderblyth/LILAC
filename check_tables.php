<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "All tables in database:\n";
    $stmt = $pdo->query('SHOW TABLES');
    while ($row = $stmt->fetch()) {
        echo "- " . $row[0] . "\n";
    }
    
    echo "\n";
    
    // Check if enhanced_documents table exists
    $stmt = $pdo->query('SHOW TABLES LIKE "enhanced_documents"');
    if ($stmt->rowCount() > 0) {
        echo "enhanced_documents table exists\n";
        $stmt = $pdo->query('DESCRIBE enhanced_documents');
        while ($row = $stmt->fetch()) {
            echo $row['Field'] . ' - ' . $row['Type'] . "\n";
        }
    } else {
        echo "enhanced_documents table does NOT exist\n";
    }
    
    // Check if documents table exists
    $stmt = $pdo->query('SHOW TABLES LIKE "documents"');
    if ($stmt->rowCount() > 0) {
        echo "\ndocuments table exists\n";
        $stmt = $pdo->query('DESCRIBE documents');
        while ($row = $stmt->fetch()) {
            echo $row['Field'] . ' - ' . $row['Type'] . "\n";
        }
    } else {
        echo "documents table does NOT exist\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?> 