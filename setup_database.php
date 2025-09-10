<?php
/**
 * Database Setup Script for LILAC
 * This script helps set up the database and tables for the LILAC system
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "✅ Database connection successful!\n";
    
    // Create basic tables if they don't exist
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE,
                sidebar_state ENUM('open','closed') NOT NULL DEFAULT 'open',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ",
        'documents' => "
            CREATE TABLE IF NOT EXISTS documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                filename VARCHAR(255) NOT NULL,
                file_size BIGINT(20) DEFAULT NULL,
                file_path VARCHAR(500) NOT NULL,
                category VARCHAR(100),
                tags TEXT,
                description TEXT,
                uploaded_by VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_trashed BOOLEAN DEFAULT FALSE,
                trashed_at TIMESTAMP NULL
            )
        ",
        'meetings' => "
            CREATE TABLE IF NOT EXISTS meetings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                date DATE NOT NULL,
                start_time TIME,
                end_time TIME,
                is_all_day BOOLEAN DEFAULT FALSE,
                color VARCHAR(50) DEFAULT 'blue',
                type VARCHAR(100) DEFAULT 'meeting',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_trashed BOOLEAN DEFAULT FALSE,
                trashed_at TIMESTAMP NULL
            )
        ",
        'uploaded_awards' => "
            CREATE TABLE IF NOT EXISTS uploaded_awards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                category VARCHAR(100) NULL,
                requirements TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        "
    ];
    
    foreach ($tables as $tableName => $sql) {
        $pdo->exec($sql);
        echo "✅ Table '$tableName' created/verified\n";
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "You can now use the LILAC system.\n";
    
} catch (Exception $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration in config/database.php\n";
    echo "Make sure MySQL is running and the database 'lilac_db' exists.\n";
}
?>
