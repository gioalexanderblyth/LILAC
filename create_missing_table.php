<?php
/**
 * Create missing file_processing_log table
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "Creating file_processing_log table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS file_processing_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_id INT NULL,
        file_type VARCHAR(50) NOT NULL,
        processing_status ENUM('processing', 'completed', 'failed') DEFAULT 'processing',
        extracted_content_length INT NULL,
        processing_time_ms INT NULL,
        error_message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        INDEX idx_status (processing_status),
        INDEX idx_file_type (file_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ file_processing_log table created successfully!\n";
    
    // Test the table
    $stmt = $pdo->query("SHOW TABLES LIKE 'file_processing_log'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table verification successful!\n";
    } else {
        echo "❌ Table verification failed!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 