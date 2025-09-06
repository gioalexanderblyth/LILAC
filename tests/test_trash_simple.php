<?php
require_once __DIR__ . '/../classes/Meeting.php';

echo "<h2>Simple Trash Test</h2>";

try {
    $meeting = new Meeting();
    
    // Get a meeting to test with
    $meetings = $meeting->getAllMeetings();
    
    if (count($meetings) > 0) {
        $testMeeting = $meetings[0];
        echo "Testing with meeting: ID=" . $testMeeting['id'] . ", Title=" . $testMeeting['title'] . "<br>";
        
        // Try to move to trash
        echo "Moving meeting to trash...<br>";
        $result = $meeting->deleteMeeting($testMeeting['id']);
        
        if ($result) {
            echo "✅ SUCCESS: Meeting moved to trash<br>";
            
            // Check if it's in trash
            $trashMeetings = $meeting->getTrashMeetings();
            echo "Meetings in trash: " . count($trashMeetings) . "<br>";
            
            if (count($trashMeetings) > 0) {
                $trashMeeting = $trashMeetings[0];
                echo "Trash meeting: ID=" . $trashMeeting['id'] . ", Original ID=" . $trashMeeting['original_id'] . ", Title=" . $trashMeeting['title'] . "<br>";
                echo "Deleted at: " . $trashMeeting['deleted_at'] . "<br>";
            }
            
            // Check if it's removed from main table
            $remainingMeetings = $meeting->getAllMeetings();
            echo "Remaining meetings: " . count($remainingMeetings) . "<br>";
            
        } else {
            echo "❌ FAILED: Could not move meeting to trash<br>";
        }
    } else {
        echo "No meetings found to test with<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "<h3>Test Complete</h3>";
?> 