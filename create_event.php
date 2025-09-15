<?php
/**
 * Direct Event Creation Handler
 */

// Handle POST request for event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once 'config/database.php';
        
        // Get form data
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $eventDate = $_POST['event_date'] ?? '';
        $eventTime = $_POST['event_time'] ?? null;
        $location = $_POST['location'] ?? '';
        $originalLink = $_POST['original_link'] ?? '';
        $awardType = $_POST['award_type'] ?? '';
        
        // Validate required fields
        if (empty($title)) {
            throw new Exception('Event title is required');
        }
        if (empty($eventDate)) {
            throw new Exception('Event date is required');
        }
        
        $db = new Database();
        $pdo = $db->getConnection();
        
        // Convert date and time to start/end DATETIME
        $start = $eventDate . ' ' . ($eventTime ?: '09:00:00');
        $end = $eventDate . ' ' . ($eventTime ? date('H:i:s', strtotime($eventTime . ' +2 hours')) : '11:00:00');
        
        // Determine status based on start date
        $startDateObj = new DateTime($start);
        $today = new DateTime();
        $status = $startDateObj < $today ? 'completed' : 'upcoming';
        
        // Insert the event
        $stmt = $pdo->prepare("
            INSERT INTO central_events (title, description, start, end, location, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            $title,
            $description,
            $start,
            $end,
            $location,
            $status
        ]);
        
        $eventId = $pdo->lastInsertId();
        
        // Redirect back to events page with success message
        $message = urlencode('Event "' . $title . '" created successfully');
        header("Location: events_activities.php?created=1&message=" . $message);
        exit;
        
    } catch (Exception $e) {
        // Redirect back with error message
        $message = urlencode('Error creating event: ' . $e->getMessage());
        header("Location: events_activities.php?error=1&message=" . $message);
        exit;
    }
} else {
    // Redirect back with error message
    $message = urlencode('Invalid request method');
    header("Location: events_activities.php?error=1&message=" . $message);
    exit;
}
?>
