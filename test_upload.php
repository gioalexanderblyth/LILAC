<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // First, ensure the table exists
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
    echo "Table created/verified\n";
    
    // Check current documents
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM enhanced_documents');
    $count = $stmt->fetch()['count'];
    echo "Current documents: " . $count . "\n";
    
    // Test the documents API
    echo "\nTesting documents API...\n";
    $url = 'http://localhost/LILAC/api/documents.php?action=get_all';
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data && isset($data['success'])) {
        echo "API Response: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        if (isset($data['documents'])) {
            echo "Documents found: " . count($data['documents']) . "\n";
        }
        if (isset($data['message'])) {
            echo "Message: " . $data['message'] . "\n";
        }
    } else {
        echo "API Response: " . $response . "\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?> 