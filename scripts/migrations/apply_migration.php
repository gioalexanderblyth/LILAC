<?php
/**
 * Database Migration Runner for LILAC System
 * Applies the file_size column migration to fix upload bug
 */

require_once 'config/database.php';

echo "LILAC Database Migration Runner\n";
echo "===============================\n\n";

try {
    // Get database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "✓ Connected to database successfully\n";
    
    // Check if file_size column already exists
    $checkQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'documents' 
                     AND COLUMN_NAME = 'file_size'";
    
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute();
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "✓ file_size column already exists in documents table\n";
        echo "✓ No migration needed\n";
    } else {
        echo "⚠ file_size column not found, applying migration...\n";
        
        // Apply the migration
        $migrationSQL = "ALTER TABLE `documents` ADD COLUMN `file_size` BIGINT(20) DEFAULT NULL AFTER `filename`";
        $conn->exec($migrationSQL);
        echo "✓ Added file_size column to documents table\n";
        
        // Update existing records
        $updateSQL = "UPDATE `documents` SET `file_size` = 0 WHERE `file_size` IS NULL";
        $updatedRows = $conn->exec($updateSQL);
        echo "✓ Updated {$updatedRows} existing records with default file_size\n";
        
        echo "✓ Migration completed successfully!\n";
    }
    
    // Verify the final structure
    echo "\nVerifying table structure:\n";
    $verifyQuery = "DESCRIBE documents";
    $stmt = $conn->prepare($verifyQuery);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Documents table columns:\n";
    foreach ($columns as $column) {
        $required = $column['Null'] === 'NO' ? ' (required)' : '';
        echo "  - {$column['Field']}: {$column['Type']}{$required}\n";
    }
    
    echo "\n✅ Database migration completed successfully!\n";
    echo "You can now upload files without the 0-byte error.\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 