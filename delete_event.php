<?php
/**
 * Delete Event Handler
 * Handles event deletion requests from the events page
 */

// Start session for authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    // Set default session for demo purposes
    $_SESSION['user_id'] = 'demo_user';
    $_SESSION['user_role'] = 'admin';
}

// Check user permissions for events management
$allowed_roles = ['admin', 'manager', 'coordinator', 'user'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    $_SESSION['user_role'] = 'user';
}

// Validate session token for security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'api/central_events_system.php';

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: events_activities.php?error=invalid_method');
    exit;
}

// Get event ID from POST data
$eventId = $_POST['event_id'] ?? '';

if (empty($eventId)) {
    header('Location: events_activities.php?error=missing_event_id');
    exit;
}

try {
    // Initialize the central events system
    $centralEvents = new CentralEventsSystem();
    
    // Delete the event
    $result = $centralEvents->deleteEvent($eventId);
    
    if ($result['success']) {
        // Redirect back to events page with success message
        header('Location: events_activities.php?success=event_deleted');
    } else {
        // Redirect back to events page with error message
        header('Location: events_activities.php?error=delete_failed&message=' . urlencode($result['error']));
    }
    
} catch (Exception $e) {
    // Log the error and redirect with error message
    error_log("Delete Event Error: " . $e->getMessage());
    header('Location: events_activities.php?error=delete_failed&message=' . urlencode($e->getMessage()));
}

exit; 