<?php
// Test the API endpoint directly
echo "<h2>Testing API Endpoint Directly</h2>";

// Simulate a POST request to the API
$_POST['action'] = 'delete';
$_POST['id'] = '1'; // Test with ID 1

echo "Testing with action: " . $_POST['action'] . "<br>";
echo "Testing with ID: " . $_POST['id'] . "<br>";

// Include the API file
ob_start();
include 'api/scheduler.php';
$output = ob_get_clean();

echo "<h3>API Response:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Also test if we can connect to the database
echo "<h3>Database Connection Test:</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "✅ Database connection successful<br>";
        
        // Check if meetings table has data
        $stmt = $conn->query("SELECT COUNT(*) as count FROM meetings");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Meetings in database: " . $count . "<br>";
        
        if ($count > 0) {
            $stmt = $conn->query("SELECT id, title FROM meetings LIMIT 1");
            $meeting = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Sample meeting: ID=" . $meeting['id'] . ", Title=" . $meeting['title'] . "<br>";
        }
        
        // Check if trash table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'meetings_trash'");
        $trashExists = $stmt->rowCount() > 0;
        echo "Trash table exists: " . ($trashExists ? "YES" : "NO") . "<br>";
        
        if ($trashExists) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM meetings_trash");
            $trashCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "Items in trash: " . $trashCount . "<br>";
        }
        
    } else {
        echo "❌ Database connection failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h3>Test Complete</h3>";
?> 