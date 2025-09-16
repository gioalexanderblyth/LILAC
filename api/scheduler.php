<?php
require_once '../classes/SchedulerManager.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$schedulerManager = new SchedulerManager();

function api_respond($success, $data = [], $error = null) {
    $response = ['success' => $success, 'data' => $data];
    if ($error) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'get_events':
            $result = $schedulerManager->loadAllEvents();
            api_respond($result['success'], $result['data'], $result['error'] ?? null);
            break;
            
        case 'get_events_for_date':
            $date = $_GET['date'] ?? '';
            if (empty($date)) api_respond(false, [], 'Date parameter is required');
            $result = $schedulerManager->getEventsForDate($date);
            api_respond($result['success'], $result['data'], $result['error'] ?? null);
            break;
            
        case 'get_event':
            $eventId = $_GET['id'] ?? '';
            if (empty($eventId)) api_respond(false, [], 'Event ID is required');
            $result = $schedulerManager->getEventById($eventId);
            if ($result['success']) {
                api_respond(true, $result['event']);
            } else {
                api_respond(false, [], $result['error']);
            }
            break;
            
        case 'get_upcoming_events':
            $limit = $_GET['limit'] ?? 5;
            $result = $schedulerManager->getUpcomingEvents($limit);
            api_respond($result['success'], $result['data'], $result['error'] ?? null);
            break;
            
        case 'create_event':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_respond(false, [], 'Method not allowed');
            $eventData = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'start' => $_POST['start'] ?? '',
                'end' => $_POST['end'] ?? '',
                'location' => $_POST['location'] ?? ''
            ];
            $result = $schedulerManager->createEvent($eventData);
            api_respond($result['success'], $result, $result['error'] ?? null);
            break;
            
        case 'update_event':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_respond(false, [], 'Method not allowed');
            $eventId = $_POST['id'] ?? '';
            if (empty($eventId)) api_respond(false, [], 'Event ID is required');
            $eventData = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'start' => $_POST['start'] ?? '',
                'end' => $_POST['end'] ?? '',
                'location' => $_POST['location'] ?? ''
            ];
            $result = $schedulerManager->updateEvent($eventId, $eventData);
            api_respond($result['success'], $result, $result['error'] ?? null);
            break;
            
        case 'delete_event':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_respond(false, [], 'Method not allowed');
            $eventId = $_POST['id'] ?? '';
            if (empty($eventId)) api_respond(false, [], 'Event ID is required');
            $result = $schedulerManager->deleteEvent($eventId);
            api_respond($result['success'], $result, $result['error'] ?? null);
            break;
            
        default:
            api_respond(false, [], 'Invalid action');
            break;
    }
} catch (Exception $e) {
    error_log("Scheduler API Error: " . $e->getMessage());
    api_respond(false, [], 'Internal server error');
}
?>