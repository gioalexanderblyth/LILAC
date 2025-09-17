<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Create enhanced_documents table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS enhanced_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_name VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        category VARCHAR(100) DEFAULT 'Awards',
        description TEXT,
        extracted_content LONGTEXT,
        award_assignments JSON,
        analysis_data JSON,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "enhanced_documents table created/verified successfully\n";
    
    // Check if table exists and show structure
    $stmt = $pdo->query('DESCRIBE enhanced_documents');
    echo "\nTable structure:\n";
    while ($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    // Check if there are any existing documents
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM enhanced_documents');
    $count = $stmt->fetch()['count'];
    echo "\nCurrent documents in table: " . $count . "\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?> 