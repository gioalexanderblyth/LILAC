<?php
require_once 'config/database.php';

echo "<h2>Diagnosing Trash Functionality Issues</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h3>1. Database Connection</h3>";
    echo "Database connection: " . ($conn ? "SUCCESS" : "FAILED") . "<br>";
    
    if ($conn) {
        echo "<h3>2. Checking Tables</h3>";
        
        // Check if meetings table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'meetings'");
        $meetingsTableExists = $stmt->rowCount() > 0;
        echo "Meetings table exists: " . ($meetingsTableExists ? "YES" : "NO") . "<br>";
        
        // Check if meetings_trash table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'meetings_trash'");
        $trashTableExists = $stmt->rowCount() > 0;
        echo "Meetings_trash table exists: " . ($trashTableExists ? "YES" : "NO") . "<br>";
        
        if ($trashTableExists) {
            echo "<h3>3. Checking Trash Table Structure</h3>";
            $stmt = $conn->query("DESCRIBE meetings_trash");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Trash table columns:<br>";
            foreach ($columns as $column) {
                echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
            }
            
            // Check if required columns exist
            $requiredColumns = ['id', 'original_id', 'title', 'meeting_date', 'meeting_time', 'end_date', 'end_time', 'is_all_day', 'color', 'deleted_at'];
            $missingColumns = [];
            
            foreach ($requiredColumns as $required) {
                $found = false;
                foreach ($columns as $column) {
                    if ($column['Field'] === $required) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $missingColumns[] = $required;
                }
            }
            
            if (empty($missingColumns)) {
                echo "All required columns exist in trash table<br>";
            } else {
                echo "Missing columns in trash table: " . implode(', ', $missingColumns) . "<br>";
            }
        }
        
        echo "<h3>4. Checking Sample Data</h3>";
        
        // Check meetings table
        $stmt = $conn->query("SELECT COUNT(*) as count FROM meetings");
        $meetingsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Meetings in main table: " . $meetingsCount . "<br>";
        
        if ($meetingsCount > 0) {
            $stmt = $conn->query("SELECT id, title, meeting_date, meeting_time, end_date, end_time, is_all_day, color FROM meetings LIMIT 1");
            $sampleMeeting = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Sample meeting: ID=" . $sampleMeeting['id'] . ", Title=" . $sampleMeeting['title'] . "<br>";
        }
        
        // Check trash table
        if ($trashTableExists) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM meetings_trash");
            $trashCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "Meetings in trash: " . $trashCount . "<br>";
            
            if ($trashCount > 0) {
                $stmt = $conn->query("SELECT id, original_id, title, deleted_at FROM meetings_trash LIMIT 1");
                $sampleTrash = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "Sample trash item: ID=" . $sampleTrash['id'] . ", Original ID=" . $sampleTrash['original_id'] . ", Title=" . $sampleTrash['title'] . "<br>";
            }
        }
        
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "<h3>Diagnosis Complete</h3>";
?> 