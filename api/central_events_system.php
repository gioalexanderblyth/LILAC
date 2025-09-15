<?php
/**
 * Centralized Events Management System
 * 
 * Rules:
 * 1. Status auto-determined: If event.date < today → "Completed", Else → "Upcoming"
 * 2. Events Page: Display events grouped by status from central table
 * 3. Scheduler: Auto-insert events from central table
 * 4. Awards: Crossmatch against central table
 * 5. Sync: All modules connected to same events table, no manual duplication
 */

require_once 'config/database.php';

class CentralEventsSystem {
    private $pdo;
    
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->createCentralEventsTable();
    }
    
    /**
     * Create the central events table
     */
    private function createCentralEventsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS central_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                event_date DATE NOT NULL,
                event_time TIME,
                location VARCHAR(255),
                image_path VARCHAR(500),
                status ENUM('upcoming', 'completed') NOT NULL DEFAULT 'upcoming',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_event_date (event_date),
                INDEX idx_status_date (status, event_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Create or update an event in the central table
     */
    public function saveEvent($eventData) {
        try {
            // Determine status based on date
            $eventDate = new DateTime($eventData['event_date']);
            $today = new DateTime();
            $status = $eventDate < $today ? 'completed' : 'upcoming';
            
            // Check if event already exists (by title and date)
            $stmt = $this->pdo->prepare("
                SELECT id FROM central_events 
                WHERE title = ? AND event_date = ?
            ");
            $stmt->execute([$eventData['title'], $eventData['event_date']]);
            $existingEvent = $stmt->fetch();
            
            if ($existingEvent) {
                // Update existing event
                $stmt = $this->pdo->prepare("
                    UPDATE central_events 
                    SET description = ?, event_time = ?, location = ?, image_path = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([
                    $eventData['description'] ?? null,
                    $eventData['event_time'] ?? null,
                    $eventData['location'] ?? null,
                    $eventData['image_path'] ?? null,
                    $status,
                    $existingEvent['id']
                ]);
                
                return [
                    'success' => true,
                    'event_id' => $existingEvent['id'],
                    'status' => $status,
                    'action' => 'updated'
                ];
            } else {
                // Insert new event
                $stmt = $this->pdo->prepare("
                    INSERT INTO central_events (title, description, event_date, event_time, location, image_path, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $eventData['title'],
                    $eventData['description'] ?? null,
                    $eventData['event_date'],
                    $eventData['event_time'] ?? null,
                    $eventData['location'] ?? null,
                    $eventData['image_path'] ?? null,
                    $status
                ]);
                
                return [
                    'success' => true,
                    'event_id' => $this->pdo->lastInsertId(),
                    'status' => $status,
                    'action' => 'created'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all events grouped by status
     */
    public function getEventsByStatus() {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM central_events 
                ORDER BY event_date ASC, event_time ASC
            ");
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $grouped = [
                'upcoming' => [],
                'completed' => []
            ];
            
            foreach ($events as $event) {
                $grouped[$event['status']][] = $event;
            }
            
            return [
                'success' => true,
                'events' => $grouped,
                'total' => count($events)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get events for scheduler (upcoming events only)
     */
    public function getEventsForScheduler() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, event_date, event_time, location, status
                FROM central_events 
                WHERE status = 'upcoming'
                ORDER BY event_date ASC, event_time ASC
            ");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'events' => $events
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get events for awards crossmatching
     */
    public function getEventsForAwards() {
        try {
            $stmt = $this->pdo->query("
                SELECT id, title, description, event_date, location, status
                FROM central_events 
                ORDER BY event_date DESC
            ");
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'events' => $events
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update event statuses based on current date
     */
    public function updateEventStatuses() {
        try {
            $today = date('Y-m-d');
            
            // Update past events to completed
            $stmt = $this->pdo->prepare("
                UPDATE central_events 
                SET status = 'completed', updated_at = CURRENT_TIMESTAMP
                WHERE event_date < ? AND status = 'upcoming'
            ");
            $stmt->execute([$today]);
            $completedCount = $stmt->rowCount();
            
            // Update future events to upcoming
            $stmt = $this->pdo->prepare("
                UPDATE central_events 
                SET status = 'upcoming', updated_at = CURRENT_TIMESTAMP
                WHERE event_date >= ? AND status = 'completed'
            ");
            $stmt->execute([$today]);
            $upcomingCount = $stmt->rowCount();
            
            return [
                'success' => true,
                'completed_updated' => $completedCount,
                'upcoming_updated' => $upcomingCount
            ];
        } catch (Exception $e) {
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
            $stmt = $this->pdo->prepare("DELETE FROM central_events WHERE id = ?");
            $stmt->execute([$eventId]);
            
            return [
                'success' => true,
                'deleted' => $stmt->rowCount() > 0
            ];
        } catch (Exception $e) {
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
            $stmt = $this->pdo->prepare("SELECT * FROM central_events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'event' => $event
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Migrate existing events from enhanced_events to central_events
     */
    public function migrateExistingEvents() {
        try {
            // Check if enhanced_events table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'enhanced_events'");
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => true,
                    'message' => 'No enhanced_events table found, nothing to migrate'
                ];
            }
            
            // Get existing events
            $stmt = $this->pdo->query("
                SELECT title, description, event_date, event_time, location, file_path as image_path
                FROM enhanced_events
            ");
            $existingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $migrated = 0;
            $errors = 0;
            
            foreach ($existingEvents as $event) {
                $result = $this->saveEvent($event);
                if ($result['success']) {
                    $migrated++;
                } else {
                    $errors++;
                }
            }
            
            return [
                'success' => true,
                'migrated' => $migrated,
                'errors' => $errors,
                'total' => count($existingEvents)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
