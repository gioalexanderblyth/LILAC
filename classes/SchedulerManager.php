<?php
/**
 * Scheduler Manager Class
 * Handles all scheduler-related database operations and business logic
 */

require_once 'config/database.php';

class SchedulerManager {
    private $pdo;
    
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
    }
    
    /**
     * Load meetings data from database (single source of truth)
     */
    public function loadMeetingsData() {
        try {
            // Get current date range for efficient querying
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
            
            // Query only events for current week to improve performance
            $stmt = $this->pdo->prepare("
                SELECT id, title, description, start, end, location, status, created_at
                FROM central_events 
                WHERE start BETWEEN ? AND ?
                ORDER BY start ASC
            ");
            $stmt->execute([$startOfWeek, $endOfWeek]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format events for scheduler display
            $formattedEvents = [];
            foreach ($events as $event) {
                $formattedEvents[] = [
                    'id' => $event['id'],
                    'title' => $event['title'],
                    'description' => $event['description'],
                    'start' => $event['start'],
                    'end' => $event['end'],
                    'location' => $event['location'],
                    'status' => $event['status'],
                    'created_at' => $event['created_at']
                ];
            }
            
            return [
                'success' => true,
                'data' => $formattedEvents,
                'count' => count($formattedEvents)
            ];
            
        } catch (Exception $e) {
            // Log specific error for debugging
            error_log("SchedulerManager::loadMeetingsData() Error: " . $e->getMessage());
            
            return [
                'success' => false,
                'data' => [],
                'count' => 0,
                'error' => 'Failed to load meetings data. Please try again later.'
            ];
        }
    }
    
    /**
     * Load all events for calendar view
     */
    public function loadAllEvents() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, description, start, end, location, status
                FROM central_events 
                ORDER BY start ASC
            ");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $events,
                'count' => count($events)
            ];
            
        } catch (Exception $e) {
            error_log("SchedulerManager::loadAllEvents() Error: " . $e->getMessage());
            
            return [
                'success' => false,
                'data' => [],
                'count' => 0,
                'error' => 'Failed to load events data.'
            ];
        }
    }
    
    /**
     * Get events for specific date
     */
    public function getEventsForDate($date) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, description, start, end, location, status
                FROM central_events 
                WHERE DATE(start) = ?
                ORDER BY start ASC
            ");
            $stmt->execute([$date]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $events,
                'count' => count($events)
            ];
            
        } catch (Exception $e) {
            error_log("SchedulerManager::getEventsForDate() Error: " . $e->getMessage());
            
            return [
                'success' => false,
                'data' => [],
                'count' => 0,
                'error' => 'Failed to load events for date.'
            ];
        }
    }
    
    /**
     * Create a new event
     */
    public function createEvent($eventData) {
        try {
            // Validate required fields
            if (empty($eventData['title'])) {
                throw new Exception('Event title is required');
            }
            if (empty($eventData['start'])) {
                throw new Exception('Event start time is required');
            }
            
            // Sanitize input data
            $title = trim($eventData['title']);
            $description = trim($eventData['description'] ?? '');
            $start = $eventData['start'];
            $end = $eventData['end'] ?? $start;
            $location = trim($eventData['location'] ?? '');
            
            // Determine status based on start date
            $startDate = new DateTime($start);
            $today = new DateTime();
            $status = $startDate < $today ? 'completed' : 'upcoming';
            
            $stmt = $this->pdo->prepare("
                INSERT INTO central_events (title, description, start, end, location, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$title, $description, $start, $end, $location, $status]);
            
            $eventId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'event_id' => $eventId,
                'message' => 'Event created successfully'
            ];
            
        } catch (Exception $e) {
            error_log("SchedulerManager::createEvent() Error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update an existing event
     */
    public function updateEvent($eventId, $eventData) {
        try {
            // Validate event exists
            $existingEvent = $this->getEventById($eventId);
            if (!$existingEvent['success']) {
                throw new Exception('Event not found');
            }
            
            // Validate required fields
            if (empty($eventData['title'])) {
                throw new Exception('Event title is required');
            }
            if (empty($eventData['start'])) {
                throw new Exception('Event start time is required');
            }
            
            // Sanitize input data
            $title = trim($eventData['title']);
            $description = trim($eventData['description'] ?? '');
            $start = $eventData['start'];
            $end = $eventData['end'] ?? $start;
            $location = trim($eventData['location'] ?? '');
            
            // Determine status based on start date
            $startDate = new DateTime($start);
            $today = new DateTime();
            $status = $startDate < $today ? 'completed' : 'upcoming';
            
            $stmt = $this->pdo->prepare("
                UPDATE central_events 
                SET title = ?, description = ?, start = ?, end = ?, location = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([$title, $description, $start, $end, $location, $status, $eventId]);
            
            return [
                'success' => true,
                'message' => 'Event updated successfully'
            ];
            
        } catch (Exception $e) {
            error_log("SchedulerManager::updateEvent() Error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get event by ID
     */
    public function getEventById($eventId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, description, start, end, location, status, created_at, updated_at
                FROM central_events 
                WHERE id = ?
            ");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                return [
                    'success' => true,
                    'event' => $event
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Event not found'
                ];
            }
            
        } catch (Exception $e) {
            error_log("SchedulerManager::getEventById() Error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete an event
     */
    public function deleteEvent($eventId) {
        try {
            // Validate event exists
            $existingEvent = $this->getEventById($eventId);
            if (!$existingEvent['success']) {
                throw new Exception('Event not found');
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM central_events WHERE id = ?");
            $stmt->execute([$eventId]);
            
            return [
                'success' => true,
                'message' => 'Event deleted successfully'
            ];
            
        } catch (Exception $e) {
            error_log("SchedulerManager::deleteEvent() Error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get upcoming events for reminders
     */
    public function getUpcomingEvents($limit = 5) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, start, location
                FROM central_events 
                WHERE start > NOW() AND status = 'upcoming'
                ORDER BY start ASC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $events,
                'count' => count($events)
            ];
            
        } catch (Exception $e) {
            error_log("SchedulerManager::getUpcomingEvents() Error: " . $e->getMessage());
            
            return [
                'success' => false,
                'data' => [],
                'count' => 0,
                'error' => 'Failed to load upcoming events.'
            ];
        }
    }
}
?>
