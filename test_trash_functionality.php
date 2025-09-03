<?php
require_once 'classes/Meeting.php';

echo "<h2>Testing Trash Functionality</h2>";

try {
    $meeting = new Meeting();
    
    // Test 1: Check if we can get meetings
    echo "<h3>Test 1: Getting all meetings</h3>";
    $meetings = $meeting->getAllMeetings();
    echo "Found " . count($meetings) . " meetings<br>";
    
    if (count($meetings) > 0) {
        $testMeeting = $meetings[0];
        echo "Testing with meeting ID: " . $testMeeting['id'] . " - " . $testMeeting['title'] . "<br>";
        
        // Test 2: Try to move a meeting to trash
        echo "<h3>Test 2: Moving meeting to trash</h3>";
        $result = $meeting->deleteMeeting($testMeeting['id']);
        echo "Move to trash result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
        
        if ($result) {
            // Test 3: Check if meeting is in trash
            echo "<h3>Test 3: Checking trash</h3>";
            $trashMeetings = $meeting->getTrashMeetings();
            echo "Found " . count($trashMeetings) . " meetings in trash<br>";
            
            if (count($trashMeetings) > 0) {
                $trashMeeting = $trashMeetings[0];
                echo "Trash meeting: ID " . $trashMeeting['original_id'] . " - " . $trashMeeting['title'] . "<br>";
                echo "Deleted at: " . $trashMeeting['deleted_at'] . "<br>";
                
                // Test 4: Try to restore the meeting
                echo "<h3>Test 4: Restoring meeting from trash</h3>";
                $restoreResult = $meeting->restoreMeeting($trashMeeting['id']);
                echo "Restore result: " . ($restoreResult ? 'SUCCESS' : 'FAILED') . "<br>";
                
                if ($restoreResult) {
                    echo "<h3>Test 5: Verifying restoration</h3>";
                    $meetingsAfterRestore = $meeting->getAllMeetings();
                    echo "Meetings after restore: " . count($meetingsAfterRestore) . "<br>";
                    
                    $trashAfterRestore = $meeting->getTrashMeetings();
                    echo "Trash after restore: " . count($trashAfterRestore) . "<br>";
                }
            }
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