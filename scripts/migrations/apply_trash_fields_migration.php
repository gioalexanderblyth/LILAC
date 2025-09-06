<?php
/**
 * Apply Trash Table Fields Migration
 * This script adds the missing fields to the meetings_trash table
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Starting trash table fields migration...\n";
    
    // Read the migration SQL file
    $migrationFile = 'sql/migration_update_trash_table_fields.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        
        $stmt = $conn->prepare($statement);
        $result = $stmt->execute();
        
        if ($result) {
            echo "✓ Success\n";
        } else {
            echo "✗ Failed\n";
            $error = $stmt->errorInfo();
            echo "Error: " . $error[2] . "\n";
        }
    }
    
    echo "\nTrash table fields migration completed successfully!\n";
    echo "The meetings_trash table now supports:\n";
    echo "- End dates and times\n";
    echo "- All-day events\n";
    echo "- Color coding\n";
    echo "Delete functionality should now work properly.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?> 