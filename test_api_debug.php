<?php
// Test file to debug the API issue and create missing table
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing API and creating missing table...\n";

try {
    // Test database connection
    require_once 'config/database.php';
    $db = new Database();
    $pdo = $db->getConnection();
    echo "✅ Database connection successful\n";
    
    // Create missing file_processing_log table
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
    
    // Test central events system
    require_once 'api/central_events_system.php';
    $centralEvents = new CentralEventsSystem();
    echo "✅ Central events system loaded\n";
    
    // Test creating an event
    $eventData = [
        'title' => 'Test Event',
        'description' => 'Test Description',
        'event_date' => '2024-12-20',
        'event_time' => '12:00:00',
        'location' => 'Test Location'
    ];
    
    $result = $centralEvents->saveEvent($eventData);
    echo "✅ Event creation result: " . json_encode($result) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 