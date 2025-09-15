<?php
/**
 * Direct Event Deletion Endpoint
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    try {
        require_once 'config/database.php';
        
        $eventId = (int)$_POST['event_id'];
        
        if ($eventId <= 0) {
            throw new Exception('Invalid event ID');
        }
        
        $db = new Database();
        $pdo = $db->getConnection();
        
        // First, check if the event exists
        $stmt = $pdo->prepare("SELECT id, title FROM central_events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            echo json_encode([
                'success' => false,
                'message' => 'Event not found'
            ]);
            exit;
        }
        
        // Delete the event from central_events table
        $stmt = $pdo->prepare("DELETE FROM central_events WHERE id = ?");
        $stmt->execute([$eventId]);
        
        if ($stmt->rowCount() > 0) {
            // Redirect back to events page with success message
            $message = urlencode('Event "' . $event['title'] . '" deleted successfully');
            header("Location: events_activities.php?deleted=1&message=" . $message);
            exit;
        } else {
            // Redirect back with error message
            $message = urlencode('Event could not be deleted');
            header("Location: events_activities.php?error=1&message=" . $message);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting event: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method or missing event ID'
    ]);
}
?>
