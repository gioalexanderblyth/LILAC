<?php
/**
 * Direct Trash Event Deletion Handler
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trash_id'])) {
    try {
        require_once 'config/database.php';
        
        $trashId = $_POST['trash_id'];
        
        // Debug: Log the received trash ID
        error_log("Received trash_id: " . var_export($trashId, true));
        
        if (empty($trashId) || !is_numeric($trashId) || (int)$trashId <= 0) {
            throw new Exception('Invalid trash ID: ' . var_export($trashId, true));
        }
        
        $trashId = (int)$trashId;
        
        $db = new Database();
        $pdo = $db->getConnection();
        
        // First, check if the trash event exists
        $stmt = $pdo->prepare("SELECT id, title FROM event_trash WHERE id = ?");
        $stmt->execute([$trashId]);
        $trashEvent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$trashEvent) {
            echo json_encode([
                'success' => false,
                'message' => 'Trash event not found'
            ]);
            exit;
        }
        
        // Delete the event from event_trash table
        $stmt = $pdo->prepare("DELETE FROM event_trash WHERE id = ?");
        $stmt->execute([$trashId]);
        
        if ($stmt->rowCount() > 0) {
            // Redirect back to events page with success message
            $message = urlencode('Event "' . $trashEvent['title'] . '" permanently deleted');
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
        'message' => 'Invalid request method or missing trash ID'
    ]);
}
?>
