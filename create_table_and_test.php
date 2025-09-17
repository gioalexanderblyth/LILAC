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
    echo "Table created/verified successfully\n";
    
    // Check current documents
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM enhanced_documents');
    $count = $stmt->fetch()['count'];
    echo "Current documents in table: " . $count . "\n";
    
    // Test the documents API by making a direct call
    echo "\nTesting documents API...\n";
    
    // Simulate the API call that the frontend makes
    $url = 'http://localhost/LILAC/api/documents.php?action=get_all&page=1&limit=10&sort_by=upload_date&sort_order=DESC';
    
    // Use cURL to test the API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            echo "API Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
            if (isset($data['documents'])) {
                echo "Documents found: " . count($data['documents']) . "\n";
                if (count($data['documents']) > 0) {
                    echo "First document: " . $data['documents'][0]['document_name'] . "\n";
                }
            }
            if (isset($data['message'])) {
                echo "Message: " . $data['message'] . "\n";
            }
        } else {
            echo "Invalid JSON response\n";
        }
    } else {
        echo "No response from API\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?> 