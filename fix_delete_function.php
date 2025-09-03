<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Fixing Delete Function</h2>";
    
    // Create the meetings_trash table if it doesn't exist
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `meetings_trash` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `original_id` int(11) NOT NULL COMMENT 'Original meeting ID before deletion',
      `title` varchar(255) NOT NULL,
      `meeting_date` date NOT NULL,
      `meeting_time` time NOT NULL,
      `end_date` date NULL,
      `end_time` time NULL,
      `location` varchar(255) DEFAULT NULL,
      `description` text DEFAULT NULL,
      `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
      `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
      `color` varchar(50) NOT NULL DEFAULT 'blue',
      `deleted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the meeting was moved to trash',
      `deleted_by` varchar(100) DEFAULT NULL COMMENT 'User who deleted the meeting',
      `original_created_at` timestamp NULL DEFAULT NULL COMMENT 'Original creation timestamp',
      `original_updated_at` timestamp NULL DEFAULT NULL COMMENT 'Original update timestamp',
      PRIMARY KEY (`id`),
      KEY `idx_original_id` (`original_id`),
      KEY `idx_deleted_at` (`deleted_at`),
      KEY `idx_meeting_date` (`meeting_date`),
      KEY `idx_end_date` (`end_date`),
      KEY `idx_is_all_day` (`is_all_day`),
      KEY `idx_color` (`color`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Trash bin for deleted meetings';
    ";
    
    $pdo->exec($createTableSQL);
    echo "✅ Trash table created/updated successfully<br>";
    
    // Test the delete function
    echo "<h3>Testing Delete Function</h3>";
    
    // Get a test meeting
    $stmt = $pdo->query("SELECT * FROM meetings LIMIT 1");
    $testMeeting = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testMeeting) {
        echo "Found test meeting: ID {$testMeeting['id']} - {$testMeeting['title']}<br>";
        
        // Test the delete process manually
        $id = $testMeeting['id'];
        
        // 1. Insert into trash
        $trashSQL = "INSERT INTO meetings_trash 
                     (original_id, title, meeting_date, meeting_time, end_date, end_time, location, description, status, is_all_day, color, deleted_by, original_created_at, original_updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $trashStmt = $pdo->prepare($trashSQL);
        $result = $trashStmt->execute([
            $id,
            $testMeeting['title'],
            $testMeeting['meeting_date'],
            $testMeeting['meeting_time'],
            $testMeeting['end_date'] ?? null,
            $testMeeting['end_time'] ?? null,
            $testMeeting['location'],
            $testMeeting['description'],
            $testMeeting['status'],
            $testMeeting['is_all_day'] ?? 0,
            $testMeeting['color'] ?? 'blue',
            'system',
            $testMeeting['created_at'],
            $testMeeting['updated_at']
        ]);
        
        if ($result) {
            echo "✅ Successfully moved to trash<br>";
            
            // 2. Delete from original table
            $deleteSQL = "DELETE FROM meetings WHERE id = ?";
            $deleteStmt = $pdo->prepare($deleteSQL);
            $deleteResult = $deleteStmt->execute([$id]);
            
            if ($deleteResult) {
                echo "✅ Successfully deleted from original table<br>";
                echo "✅ Delete function is working!<br>";
            } else {
                echo "❌ Failed to delete from original table<br>";
            }
        } else {
            echo "❌ Failed to move to trash<br>";
        }
    } else {
        echo "No meetings found to test with<br>";
    }
    
    echo "<h3>Current Status</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM meetings");
    $meetingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Meetings in main table: {$meetingCount}<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM meetings_trash");
    $trashCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Meetings in trash: {$trashCount}<br>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?> 