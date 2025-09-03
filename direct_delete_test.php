<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Direct Database Delete Test</h2>";
    
    // Get all meetings
    $stmt = $pdo->query("SELECT id, title FROM meetings ORDER BY id");
    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Meetings:</h3>";
    if (count($meetings) > 0) {
        echo "<ul>";
        foreach ($meetings as $meeting) {
            echo "<li>ID: {$meeting['id']} - {$meeting['title']}</li>";
        }
        echo "</ul>";
        
        // Test delete the first meeting
        $firstMeeting = $meetings[0];
        echo "<h3>Testing Delete for Meeting ID: {$firstMeeting['id']}</h3>";
        
        $deleteSQL = "DELETE FROM meetings WHERE id = ?";
        $deleteStmt = $pdo->prepare($deleteSQL);
        $result = $deleteStmt->execute([$firstMeeting['id']]);
        
        if ($result) {
            echo "✅ SUCCESS: Meeting ID {$firstMeeting['id']} was deleted!<br>";
            
            // Check remaining meetings
            $stmt = $pdo->query("SELECT id, title FROM meetings ORDER BY id");
            $remainingMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Remaining Meetings:</h3>";
            if (count($remainingMeetings) > 0) {
                echo "<ul>";
                foreach ($remainingMeetings as $meeting) {
                    echo "<li>ID: {$meeting['id']} - {$meeting['title']}</li>";
                }
                echo "</ul>";
            } else {
                echo "No meetings remaining.<br>";
            }
        } else {
            echo "❌ FAILED: Could not delete meeting ID {$firstMeeting['id']}<br>";
        }
    } else {
        echo "No meetings found in database.<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?> 