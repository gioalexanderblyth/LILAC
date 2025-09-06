<?php
/**
 * Installation script for MOU Date Extraction Features
 * This script sets up the required dependencies and database tables
 */

set_time_limit(300); // 5 minutes
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>LILAC MOU Date Extraction Installation</h1>\n";
echo "<pre>\n";

// Step 1: Check PHP version
echo "=== Checking PHP Version ===\n";
$phpVersion = PHP_VERSION;
echo "Current PHP version: $phpVersion\n";

if (version_compare($phpVersion, '7.4.0', '<')) {
    echo "ERROR: PHP 7.4 or higher is required!\n";
    exit(1);
}
echo "✓ PHP version is compatible\n\n";

// Step 2: Check if Composer is available
echo "=== Checking Composer ===\n";
$composerPath = null;

// Try different composer locations
$composerPaths = [
    'composer',           // Global composer
    'composer.phar',      // Local composer.phar
    '/usr/local/bin/composer',
    '/usr/bin/composer'
];

foreach ($composerPaths as $path) {
    $output = [];
    $returnVar = 0;
    exec("$path --version 2>&1", $output, $returnVar);
    
    if ($returnVar === 0) {
        $composerPath = $path;
        echo "✓ Found Composer at: $path\n";
        echo "Composer version: " . implode(' ', $output) . "\n";
        break;
    }
}

if (!$composerPath) {
    echo "WARNING: Composer not found. Please install Composer manually:\n";
    echo "https://getcomposer.org/download/\n";
    echo "Then run: composer install\n\n";
} else {
    // Step 3: Install Composer dependencies
    echo "\n=== Installing Composer Dependencies ===\n";
    echo "Running: $composerPath install\n";
    
    $output = [];
    $returnVar = 0;
    exec("$composerPath install 2>&1", $output, $returnVar);
    
    foreach ($output as $line) {
        echo "$line\n";
    }
    
    if ($returnVar === 0) {
        echo "✓ Composer dependencies installed successfully\n\n";
    } else {
        echo "ERROR: Failed to install Composer dependencies\n";
        echo "Please run manually: composer install\n\n";
    }
}

// Step 4: Check database connection
echo "=== Checking Database Connection ===\n";
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "✓ Database connection successful\n\n";
} catch (Exception $e) {
    echo "ERROR: Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in config/database.php\n\n";
    exit(1);
}

// Step 5: Run database migration
echo "=== Running Database Migration ===\n";
try {
    $migrationFile = 'sql/migration_create_mou_moa_table.sql';
    
    if (!file_exists($migrationFile)) {
        echo "ERROR: Migration file not found: $migrationFile\n";
        exit(1);
    }
    
    $sql = file_get_contents($migrationFile);
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Ignore errors for IF NOT EXISTS statements
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✓ Database migration completed\n\n";
    
} catch (Exception $e) {
    echo "ERROR: Database migration failed: " . $e->getMessage() . "\n\n";
}

// Step 6: Test MOU classes
echo "=== Testing MOU Classes ===\n";
try {
    // Test if we can load the classes
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        echo "✓ Composer autoloader available\n";
    } else {
        echo "WARNING: Composer autoloader not found. PDF parsing may not work.\n";
    }
    
    require_once 'classes/MOUData.php';
    $mouData = new MOUData();
    
    if ($mouData->tableExists()) {
        echo "✓ MOU data table exists and is accessible\n";
        
        $stats = $mouData->getMOUStatistics();
        echo "✓ MOU statistics loaded: " . json_encode($stats) . "\n";
    } else {
        echo "ERROR: MOU data table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: Failed to test MOU classes: " . $e->getMessage() . "\n";
}

// Step 7: Check file permissions
echo "\n=== Checking File Permissions ===\n";
$directories = ['uploads/', 'classes/', 'vendor/'];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✓ $dir is writable\n";
        } else {
            echo "WARNING: $dir is not writable\n";
        }
    } else {
        echo "INFO: $dir does not exist\n";
    }
}

// Step 8: Create sample test files
echo "\n=== Creating Test Files ===\n";
try {
    // Create a simple test script
    $testScript = '<?php
// Test script for MOU date extraction
require_once "classes/MOUData.php";

try {
    $mouData = new MOUData();
    echo "MOU Data class loaded successfully\n";
    
    $stats = $mouData->getMOUStatistics();
    echo "Statistics: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>';
    
    file_put_contents('test_mou_features.php', $testScript);
    echo "✓ Created test_mou_features.php\n";
    
} catch (Exception $e) {
    echo "WARNING: Could not create test files: " . $e->getMessage() . "\n";
}

echo "\n=== Installation Summary ===\n";
echo "The MOU date extraction features have been installed.\n\n";

echo "Next steps:\n";
echo "1. Upload a MOU/MOA PDF document through the documents page\n";
echo "2. Check the MOU/MOA page to see extracted dates\n";
echo "3. Review any documents flagged for manual review\n";
echo "4. Run test_mou_features.php to verify the installation\n\n";

echo "API Endpoints available:\n";
echo "- GET api/documents.php?action=get_mou_data&mou_action=all\n";
echo "- GET api/documents.php?action=get_mou_data&mou_action=requiring_review\n";
echo "- GET api/documents.php?action=get_mou_data&mou_action=statistics\n";
echo "- POST api/documents.php?action=update_mou_data\n\n";

echo "Installation complete!\n";
echo "</pre>\n";
?> 