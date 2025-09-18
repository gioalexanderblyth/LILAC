<?php
/**
 * Enhanced Management API
 * Main API endpoint for document and event management with award integration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/award_analyzer.php';
require_once __DIR__ . '/event_manager.php';
require_once __DIR__ . '/location_analyzer.php';

// Custom respond function for this API
function api_respond($success, $data = []) {
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    require_once __DIR__ . '/../classes/DynamicFileProcessor.php';
    $fileProcessor = new DynamicFileProcessor();
    $awardAnalyzer = new AwardAnalyzer($pdo);
    $eventManager = new EventManager($pdo);
    $locationAnalyzer = new LocationAnalyzer($pdo);
    
} catch (Exception $e) {
    api_respond(false, ['message' => 'System initialization failed: ' . $e->getMessage()]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'upload_document':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_respond(false, ['message' => 'Method not allowed']);
        }
        
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            api_respond(false, ['message' => 'No file uploaded']);
        }
        
        try {
            // Process file
            $result = $fileProcessor->processFile($_FILES['file'], [
                'document_name' => $_POST['document_name'] ?? $_FILES['file']['name'],
                'description' => $_POST['description'] ?? ''
            ]);
            
            if (!$result['success']) {
                api_respond(false, ['message' => 'File processing failed']);
            }
            
            // Analyze content for awards
            $analysis = $awardAnalyzer->analyzeContent($result['extracted_content'], $result['document_name']);
            $assignments = $awardAnalyzer->determineAssignments($analysis);
            
            // Assign to awards
            $awardAnalyzer->assignDocument($result['document_id'], $assignments);
            
            // Update readiness status
            $awardAnalyzer->calculateReadinessStatus();
            
            api_respond(true, [
                'message' => 'Document uploaded and processed successfully',
                'document_id' => $result['document_id'],
                'file_path' => $result['file_path'],
                'extracted_content_length' => $result['content_length'],
                'analysis' => $analysis,
                'assignments' => $assignments
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Upload failed: ' . $e->getMessage()]);
        }
        break;
        
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
            'original_link' => $_POST['original_link'] ?? ''
        ];
        
        try {
            // Create event
            $result = $eventManager->createEvent($eventData);
            
            if (!$result['success']) {
                api_respond(false, ['message' => $result['error']]);
            }
            
            // Process file if uploaded
            if (isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                $fileResult = $fileProcessor->processEvent($eventData, $_FILES['file']);
                
                if ($fileResult['success']) {
                    // Update event with file information
                    $stmt = $pdo->prepare("
                        UPDATE enhanced_events 
                        SET file_path = ?, file_type = ?, extracted_content = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $fileResult['file_path'],
                        mime_content_type($fileResult['file_path']),
                        $fileResult['extracted_content'],
                        $result['event_id']
                    ]);
                    
                    // Re-analyze with file content
                    $analysis = $awardAnalyzer->analyzeContent($fileResult['extracted_content'], $eventData['title']);
                    $assignments = $awardAnalyzer->determineAssignments($analysis);
                    $awardAnalyzer->assignEvent($result['event_id'], $assignments);
                }
            } else {
                // Even without a file, analyze the event content for award assignment
                $content = $eventData['title'] . ' ' . $eventData['description'] . ' ' . $eventData['location'];
                $analysis = $awardAnalyzer->analyzeContent($content, $eventData['title']);
                $assignments = $awardAnalyzer->determineAssignments($analysis);
                $awardAnalyzer->assignEvent($result['event_id'], $assignments);
            }
            
            api_respond(true, [
                'message' => 'Event created successfully',
                'event_id' => $result['event_id'],
                'status' => $result['status']
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Event creation failed: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_events':
        $filters = [
            'status' => $_GET['status'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'award_key' => $_GET['award_key'] ?? null,
            'limit' => (int)($_GET['limit'] ?? 50)
        ];
        
        try {
            $events = $eventManager->getEvents($filters);
            $cards = $eventManager->generateEventCards($events);
            
            api_respond(true, [
                'events' => $events,
                'cards' => $cards,
                'count' => count($events)
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to get events: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_event':
        $eventId = (int)($_GET['id'] ?? 0);
        
        if (!$eventId) {
            api_respond(false, ['message' => 'Event ID is required']);
        }
        
        try {
            $event = $eventManager->getEventById($eventId);
            
            if (!$event) {
                api_respond(false, ['message' => 'Event not found']);
            }
            
            api_respond(true, ['event' => $event]);
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to load event: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_upcoming_events':
        $limit = (int)($_GET['limit'] ?? 10);
        
        try {
            $events = $eventManager->getUpcomingEvents($limit);
            $cards = $eventManager->generateEventCards($events);
            
            api_respond(true, [
                'events' => $events,
                'cards' => $cards,
                'count' => count($events)
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to get upcoming events: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_completed_events':
        $limit = (int)($_GET['limit'] ?? 10);
        
        try {
            $events = $eventManager->getCompletedEvents($limit);
            $cards = $eventManager->generateEventCards($events);
            
            api_respond(true, [
                'events' => $events,
                'cards' => $cards,
                'count' => count($events)
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to get completed events: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_calendar_events':
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        try {
            $events = $eventManager->getEventsForCalendar($startDate, $endDate);
            
            api_respond(true, [
                'events' => $events,
                'count' => count($events),
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to get calendar events: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_event_counters':
        try {
            $counters = $eventManager->getEventCounters();
            
            api_respond(true, [
                'counters' => $counters
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to get event counters: ' . $e->getMessage()]);
        }
        break;
        
    case 'update_event_statuses':
        try {
            $eventManager->updateEventStatuses();
            
            api_respond(true, [
                'message' => 'Event statuses updated successfully'
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to update event statuses: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_award_status':
        try {
            $report = $awardAnalyzer->getStatusReport();
            
            api_respond(true, [
                'report' => $report
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to get award status: ' . $e->getMessage()]);
        }
        break;
        
    case 'auto_classify_event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_respond(false, ['message' => 'Method not allowed']);
        }
        
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';
        
        if (empty($title)) {
            api_respond(false, ['message' => 'Event title is required for classification']);
        }
        
        try {
            // Combine all content for analysis
            $content = $title . ' ' . $description . ' ' . $location;
            
            // Analyze content for award classification
            $analysis = $awardAnalyzer->analyzeContent($content, $title);
            $assignments = $awardAnalyzer->determineAssignments($analysis);
            
            // Get the best matching award
            $bestMatch = null;
            $highestScore = 0;
            
            foreach ($assignments as $awardKey => $assignment) {
                if ($assignment['score'] > $highestScore) {
                    $highestScore = $assignment['score'];
                    $bestMatch = $awardKey;
                }
            }
            
            // Get award name from award key
            $awardName = '';
            if ($bestMatch) {
                $stmt = $pdo->prepare("SELECT award_name FROM award_types WHERE award_key = ?");
                $stmt->execute([$bestMatch]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $awardName = $result ? $result['award_name'] : '';
            }
            
            api_respond(true, [
                'award_key' => $bestMatch,
                'award_name' => $awardName,
                'confidence_score' => $highestScore,
                'all_assignments' => $assignments,
                'analysis' => $analysis
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to classify event: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_award_details':
        $awardKey = $_GET['award_key'] ?? '';
        
        if (empty($awardKey)) {
            api_respond(false, ['message' => 'Award key is required']);
        }
        
        try {
            // Get documents assigned to this award
            $stmt = $pdo->prepare("
                SELECT d.* FROM enhanced_documents d
                INNER JOIN document_award_assignments daa ON d.id = daa.document_id
                WHERE daa.award_key = ?
                ORDER BY d.upload_date DESC
            ");
            $stmt->execute([$awardKey]);
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get events assigned to this award
            $events = $eventManager->getEventsByAward($awardKey);
            
            // Get readiness data
            $stmt = $pdo->prepare("SELECT * FROM award_readiness WHERE award_key = ?");
            $stmt->execute([$awardKey]);
            $readiness = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get award type info
            $stmt = $pdo->prepare("SELECT * FROM award_types WHERE award_key = ?");
            $stmt->execute([$awardKey]);
            $awardType = $stmt->fetch(PDO::FETCH_ASSOC);
            
            api_respond(true, [
                'award_key' => $awardKey,
                'award_type' => $awardType,
                'documents' => $documents,
                'events' => $events,
                'readiness' => $readiness ? [
                    'isReady' => (bool)$readiness['is_ready'],
                    'satisfiedCriteria' => json_decode($readiness['satisfied_criteria'], true),
                    'unsatisfiedCriteria' => json_decode($readiness['unsatisfied_criteria'], true),
                    'readinessPercentage' => (float)$readiness['readiness_percentage'],
                    'totalItems' => $readiness['total_items']
                ] : null
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to get award details: ' . $e->getMessage()]);
        }
        break;
        
    case 'manual_override_document':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_respond(false, ['message' => 'Method not allowed']);
        }
        
        $documentId = (int)($_POST['document_id'] ?? 0);
        $awardKey = $_POST['award_key'] ?? '';
        $action = $_POST['action'] ?? 'add'; // 'add' or 'remove'
        
        if (!$documentId || !$awardKey) {
            api_respond(false, ['message' => 'Document ID and award key are required']);
        }
        
        try {
            $awardAnalyzer->manualOverrideDocument($documentId, $awardKey, $action);
            
            api_respond(true, [
                'message' => 'Document assignment updated successfully'
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to update document assignment: ' . $e->getMessage()]);
        }
        break;
        
    case 'manual_override_event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_respond(false, ['message' => 'Method not allowed']);
        }
        
        $eventId = (int)($_POST['event_id'] ?? 0);
        $awardKey = $_POST['award_key'] ?? '';
        $action = $_POST['action'] ?? 'add'; // 'add' or 'remove'
        
        if (!$eventId || !$awardKey) {
            api_respond(false, ['message' => 'Event ID and award key are required']);
        }
        
        try {
            $awardAnalyzer->manualOverrideEvent($eventId, $awardKey, $action);
            
            api_respond(true, [
                'message' => 'Event assignment updated successfully'
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to update event assignment: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_processing_stats':
        try {
            $stats = $fileProcessor->getProcessingStats();
            $eventStats = $eventManager->getEventStatistics();
            
            api_respond(true, [
                'file_processing' => $stats,
                'event_statistics' => $eventStats
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to get processing stats: ' . $e->getMessage()]);
        }
        break;
        
    case 'search_events':
        $query = $_GET['query'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        
        if (empty($query)) {
            api_respond(false, ['message' => 'Search query is required']);
        }
        
        try {
            $events = $eventManager->searchEvents($query, $limit);
            $cards = $eventManager->generateEventCards($events);
            
            api_respond(true, [
                'events' => $events,
                'cards' => $cards,
                'count' => count($events),
                'query' => $query
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Search failed: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete_event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_respond(false, ['message' => 'Method not allowed']);
        }
        
        $eventId = (int)($_POST['event_id'] ?? 0);
        
        if (!$eventId) {
            api_respond(false, ['message' => 'Event ID is required']);
        }
        
        try {
            $result = $eventManager->moveEventToTrash($eventId);
            
            if (!$result['success']) {
                api_respond(false, ['message' => $result['error']]);
            }
            
            api_respond(true, [
                'message' => 'Event moved to trash successfully'
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to delete event: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_trash_events':
        try {
            $trashEvents = $eventManager->getTrashEvents();
            api_respond(true, ['trash_events' => $trashEvents]);
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to load trash events: ' . $e->getMessage()]);
        }
        break;
        
    case 'restore_event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_respond(false, ['message' => 'Method not allowed']);
        }
        
        $trashId = (int)($_POST['trash_id'] ?? 0);
        
        if (!$trashId) {
            api_respond(false, ['message' => 'Trash ID is required']);
        }
        
        try {
            $result = $eventManager->restoreEvent($trashId);
            
            if (!$result['success']) {
                api_respond(false, ['message' => $result['error']]);
            }
            
            api_respond(true, [
                'message' => 'Event restored successfully'
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to restore event: ' . $e->getMessage()]);
        }
        break;
        
    case 'permanently_delete_event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_respond(false, ['message' => 'Method not allowed']);
        }
        
        $trashId = (int)($_POST['trash_id'] ?? 0);
        
        if (!$trashId) {
            api_respond(false, ['message' => 'Trash ID is required']);
        }
        
        try {
            $result = $eventManager->permanentlyDeleteEvent($trashId);
            
            if (!$result['success']) {
                api_respond(false, ['message' => $result['error']]);
            }
            
            api_respond(true, [
                'message' => 'Event permanently deleted'
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to permanently delete event: ' . $e->getMessage()]);
        }
        break;
        
    case 'empty_trash_events':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_respond(false, ['message' => 'Method not allowed']);
        }
        
        try {
            $result = $eventManager->emptyTrash();
            
            if (!$result['success']) {
                api_respond(false, ['message' => $result['error']]);
            }
            
            api_respond(true, [
                'message' => 'Trash emptied successfully'
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to empty trash: ' . $e->getMessage()]);
        }
        break;
        
    case 'update_event':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            api_respond(false, ['message' => 'Method not allowed']);
        }
        
        $eventId = (int)($_POST['event_id'] ?? 0);
        
        if (!$eventId) {
            api_respond(false, ['message' => 'Event ID is required']);
        }
        
        $eventData = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'event_date' => $_POST['event_date'] ?? '',
            'event_time' => $_POST['event_time'] ?? null,
            'location' => $_POST['location'] ?? ''
        ];
        
        try {
            $result = $eventManager->updateEvent($eventId, $eventData);
            
            if (!$result['success']) {
                api_respond(false, ['message' => $result['error']]);
            }
            
            api_respond(true, [
                'message' => 'Event updated successfully',
                'event_id' => $result['event_id'],
                'status' => $result['status']
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to update event: ' . $e->getMessage()]);
        }
        break;

    case 'analyze_location':
        try {
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $imagePath = $_POST['image_path'] ?? null;
            
            if (empty($title) && empty($description)) {
                api_respond(false, ['message' => 'Title or description is required for location analysis']);
            }
            
            $analysis = $locationAnalyzer->analyzeEventLocation($title, $description, $imagePath);
            
            api_respond(true, [
                'location_analysis' => $analysis
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Location analysis failed: ' . $e->getMessage()]);
        }
        break;

    case 'get_location_suggestions':
        try {
            $eventType = $_GET['event_type'] ?? '';
            $suggestions = $locationAnalyzer->getLocationSuggestions($eventType);
            
            api_respond(true, [
                'suggestions' => $suggestions
            ]);
            
        } catch (Exception $e) {
            api_respond(false, ['message' => 'Failed to get location suggestions: ' . $e->getMessage()]);
        }
        break;

    default:
        api_respond(false, ['message' => 'Invalid action']);
        break;
}
?>
