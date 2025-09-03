<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Connection Test</h2>";
    echo "✅ Database connection successful<br><br>";
    
    // Check if meetings_trash table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'meetings_trash'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<h3>Trash Table Check</h3>";
    if ($tableExists) {
        echo "✅ meetings_trash table exists<br>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE meetings_trash");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Table Structure:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for required columns
        $requiredColumns = ['end_date', 'end_time', 'is_all_day', 'color'];
        $existingColumns = array_column($columns, 'Field');
        
        echo "<h4>Required Columns Check:</h4>";
        foreach ($requiredColumns as $requiredColumn) {
            if (in_array($requiredColumn, $existingColumns)) {
                echo "✅ {$requiredColumn} exists<br>";
            } else {
                echo "❌ {$requiredColumn} MISSING<br>";
            }
        }
        
    } else {
        echo "❌ meetings_trash table does NOT exist<br>";
        echo "<p>You need to run the migration files:</p>";
        echo "<ol>";
        echo "<li>sql/migration_create_meetings_trash_table.sql</li>";
        echo "<li>sql/migration_update_trash_table_fields.sql</li>";
        echo "</ol>";
    }
    
    // Check if there are any meetings in the main table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM meetings");
    $meetingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<h3>Meetings Count</h3>";
    echo "Total meetings in main table: {$meetingCount}<br>";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage();
}
?> 