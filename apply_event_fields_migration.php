<?php
/**
 * Apply Event Fields Migration
 * This script adds new fields to the meetings table for enhanced event functionality
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Starting event fields migration...\n";
    
    // Read the migration SQL file
    $migrationFile = 'sql/migration_add_event_fields.sql';
    
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
    
    echo "\nEvent fields migration completed successfully!\n";
    echo "The meetings table now supports:\n";
    echo "- End dates and times\n";
    echo "- All-day events\n";
    echo "- Color coding\n";
    echo "You can now use the enhanced Add Event modal.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?> 