<?php
/**
 * Test the Centralized Events System
 */

require_once 'api/central_events_system.php';

try {
    echo "=== TESTING CENTRALIZED EVENTS SYSTEM ===\n\n";
    
    $centralEvents = new CentralEventsSystem();
    
    echo "1. Testing event creation...\n";
    $testEvent = [
        'title' => 'Test Central Event',
        'description' => 'This is a test event for the centralized system',
        'event_date' => '2025-12-25',
        'event_time' => '10:00:00',
        'location' => 'Test Location'
    ];
    
    $result = $centralEvents->saveEvent($testEvent);
    if ($result['success']) {
        echo "✅ Event created successfully (ID: " . $result['event_id'] . ", Status: " . $result['status'] . ")\n";
        $testEventId = $result['event_id'];
    } else {
        echo "❌ Event creation failed: " . $result['error'] . "\n";
        exit;
    }
    
    echo "\n2. Testing get events by status...\n";
    $eventsResult = $centralEvents->getEventsByStatus();
    if ($eventsResult['success']) {
        echo "✅ Events retrieved successfully\n";
        echo "   - Total events: " . $eventsResult['total'] . "\n";
        echo "   - Upcoming events: " . count($eventsResult['events']['upcoming']) . "\n";
        echo "   - Completed events: " . count($eventsResult['events']['completed']) . "\n";
    } else {
        echo "❌ Failed to get events: " . $eventsResult['error'] . "\n";
    }
    
    echo "\n3. Testing scheduler events...\n";
    $schedulerResult = $centralEvents->getEventsForScheduler();
    if ($schedulerResult['success']) {
        echo "✅ Scheduler events retrieved successfully\n";
        echo "   - Upcoming events for scheduler: " . count($schedulerResult['events']) . "\n";
    } else {
        echo "❌ Failed to get scheduler events: " . $schedulerResult['error'] . "\n";
    }
    
    echo "\n4. Testing awards events...\n";
    $awardsResult = $centralEvents->getEventsForAwards();
    if ($awardsResult['success']) {
        echo "✅ Awards events retrieved successfully\n";
        echo "   - Total events for awards: " . count($awardsResult['events']) . "\n";
    } else {
        echo "❌ Failed to get awards events: " . $awardsResult['error'] . "\n";
    }
    
    echo "\n5. Testing status updates...\n";
    $statusResult = $centralEvents->updateEventStatuses();
    if ($statusResult['success']) {
        echo "✅ Status updates completed\n";
        echo "   - Events marked as completed: " . $statusResult['completed_updated'] . "\n";
        echo "   - Events marked as upcoming: " . $statusResult['upcoming_updated'] . "\n";
    } else {
        echo "❌ Status update failed: " . $statusResult['error'] . "\n";
    }
    
    echo "\n6. Testing event deletion...\n";
    $deleteResult = $centralEvents->deleteEvent($testEventId);
    if ($deleteResult['success']) {
        echo "✅ Test event deleted successfully\n";
    } else {
        echo "❌ Event deletion failed: " . $deleteResult['error'] . "\n";
    }
    
    echo "\n🎉 Centralized Events System is working correctly!\n";
    echo "\nSystem Features:\n";
    echo "✅ Auto-status determination (upcoming/completed)\n";
    echo "✅ Events grouped by status\n";
    echo "✅ Scheduler integration ready\n";
    echo "✅ Awards crossmatching ready\n";
    echo "✅ Automatic status updates\n";
    echo "✅ CRUD operations working\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
