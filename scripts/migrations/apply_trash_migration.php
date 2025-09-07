<?php
require_once 'config/database.php';

echo "<h2>Applying Trash Table Migration</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "✅ Database connected successfully<br>";
    
    // Check if trash table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'meetings_trash'");
    $trashExists = $stmt->rowCount() > 0;
    
    if (!$trashExists) {
        echo "❌ Trash table does not exist. Creating it...<br>";
        
        // Create the trash table
        $createTableSQL = "
        CREATE TABLE `meetings_trash` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `original_id` int(11) NOT NULL COMMENT 'Original meeting ID before deletion',
          `title` varchar(255) NOT NULL,
          `meeting_date` date NOT NULL,
          `meeting_time` time NOT NULL,
          `end_date` date NULL,
          `end_time` time NULL,
          `is_all_day` tinyint(1) NOT NULL DEFAULT 0,
          `color` varchar(50) NOT NULL DEFAULT 'blue',
          `location` varchar(255) DEFAULT NULL,
          `description` text DEFAULT NULL,
          `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
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
        
        $result = $conn->exec($createTableSQL);
        if ($result !== false) {
            echo "✅ Trash table created successfully<br>";
        } else {
            echo "❌ Failed to create trash table<br>";
            $error = $conn->errorInfo();
            echo "Error: " . $error[2] . "<br>";
        }
    } else {
        echo "✅ Trash table already exists<br>";
        
        // Check if all required columns exist
        $stmt = $conn->query("DESCRIBE meetings_trash");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['end_date', 'end_time', 'is_all_day', 'color'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $required) {
            if (!in_array($required, $columns)) {
                $missingColumns[] = $required;
            }
        }
        
        if (!empty($missingColumns)) {
            echo "⚠️ Missing columns: " . implode(', ', $missingColumns) . "<br>";
            echo "Adding missing columns...<br>";
            
            foreach ($missingColumns as $column) {
                $alterSQL = "";
                switch ($column) {
                    case 'end_date':
                        $alterSQL = "ALTER TABLE `meetings_trash` ADD COLUMN `end_date` date NULL AFTER `meeting_time`";
                        break;
                    case 'end_time':
                        $alterSQL = "ALTER TABLE `meetings_trash` ADD COLUMN `end_time` time NULL AFTER `end_date`";
                        break;
                    case 'is_all_day':
                        $alterSQL = "ALTER TABLE `meetings_trash` ADD COLUMN `is_all_day` tinyint(1) NOT NULL DEFAULT 0 AFTER `end_time`";
                        break;
                    case 'color':
                        $alterSQL = "ALTER TABLE `meetings_trash` ADD COLUMN `color` varchar(50) NOT NULL DEFAULT 'blue' AFTER `is_all_day`";
                        break;
                }
                
                if ($alterSQL) {
                    $result = $conn->exec($alterSQL);
                    if ($result !== false) {
                        echo "✅ Added column: $column<br>";
                    } else {
                        echo "❌ Failed to add column: $column<br>";
                        $error = $conn->errorInfo();
                        echo "Error: " . $error[2] . "<br>";
                    }
                }
            }
        } else {
            echo "✅ All required columns exist<br>";
        }
    }
    
    // Final verification
    echo "<h3>Final Verification:</h3>";
    $stmt = $conn->query("DESCRIBE meetings_trash");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Trash table columns:<br>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
    }
    
    echo "<h3>Migration Complete!</h3>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?> 