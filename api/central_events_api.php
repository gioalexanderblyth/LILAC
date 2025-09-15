<?php
/**
 * Central Events API
 * Handles all event operations for the centralized system
 */

require_once 'central_events_system.php';
require_once 'config/database.php';
require_once 'universal_upload_handler.php';

// Set JSON header
header('Content-Type: application/json');

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Initialize central events system and universal upload handler
$centralEvents = new CentralEventsSystem();
$uploadHandler = new UniversalUploadHandler();

// Helper function for API responses
function api_respond($success, $data = []) {
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit;
}

try {
    switch ($action) {
        case 'create_event':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_respond(false, ['message' => 'Method not allowed']);
            }
            
            $eventData = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'event_date' => $_POST['event_date'] ?? '',
                'event_time' => $_POST['event_time'] ?? null,
                'location' => $_POST['location'] ?? '',
                'original_link' => $_POST['original_link'] ?? '',
                'award_type' => $_POST['award_type'] ?? ''
            ];
            
            // Handle file upload using universal upload handler
            if (isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                $uploadResult = $uploadHandler->handleUpload($_FILES['file'], 'system', 'events');
                
                if ($uploadResult['success']) {
                    $eventData['image_path'] = $uploadResult['file_path'];
                    $eventData['file_id'] = $uploadResult['file_id'];
                    
                    // If event date wasn't provided but was extracted from file, use it
                    if (empty($eventData['event_date']) && $uploadResult['event_date']) {
                        $eventData['event_date'] = $uploadResult['event_date'];
                    }
                } else {
                    api_respond(false, ['message' => 'File upload failed: ' . $uploadResult['error']]);
                }
            }
            
            $result = $centralEvents->saveEvent($eventData);
            api_respond($result['success'], $result);
            break;
            
        case 'get_events_by_status':
            $result = $centralEvents->getEventsByStatus();
            api_respond($result['success'], $result);
            break;
            
        case 'get_events_for_scheduler':
            $result = $centralEvents->getEventsForScheduler();
            api_respond($result['success'], $result);
            break;
            
        case 'get_events_for_awards':
            $result = $centralEvents->getEventsForAwards();
            api_respond($result['success'], $result);
            break;
            
        case 'update_statuses':
            $result = $centralEvents->updateEventStatuses();
            api_respond($result['success'], $result);
            break;
            
        case 'delete_event':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_respond(false, ['message' => 'Method not allowed']);
            }
            
            $eventId = $_POST['event_id'] ?? '';
            if (empty($eventId)) {
                api_respond(false, ['message' => 'Event ID is required']);
            }
            
            $result = $centralEvents->deleteEvent($eventId);
            api_respond($result['success'], $result);
            break;
            
        case 'get_event':
            $eventId = $_GET['event_id'] ?? '';
            if (empty($eventId)) {
                api_respond(false, ['message' => 'Event ID is required']);
            }
            
            $result = $centralEvents->getEventById($eventId);
            api_respond($result['success'], $result);
            break;
            
        case 'migrate_events':
            $result = $centralEvents->migrateExistingEvents();
            api_respond($result['success'], $result);
            break;
            
        default:
            api_respond(false, ['message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    api_respond(false, ['message' => 'Error: ' . $e->getMessage()]);
}
?>
