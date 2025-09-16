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
                start DATETIME NOT NULL,
                end DATETIME,
                location VARCHAR(255),
                image_path VARCHAR(500),
                status ENUM('upcoming', 'completed') NOT NULL DEFAULT 'upcoming',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_start (start),
                INDEX idx_status_start (status, start)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        $this->migrateTableSchema();
    }
    
    /**
     * Migrate existing table schema to new format
     */
    private function migrateTableSchema() {
        try {
            // Check if old columns exist
            $stmt = $this->pdo->query("SHOW COLUMNS FROM central_events LIKE 'event_date'");
            if ($stmt->rowCount() > 0) {
                // Add new columns if they don't exist
                $this->pdo->exec("ALTER TABLE central_events ADD COLUMN IF NOT EXISTS start DATETIME");
                $this->pdo->exec("ALTER TABLE central_events ADD COLUMN IF NOT EXISTS end DATETIME");
                
                // Migrate data from old columns to new columns
                $this->pdo->exec("
                    UPDATE central_events 
                    SET start = CONCAT(event_date, ' ', COALESCE(event_time, '09:00:00'))
                    WHERE start IS NULL AND event_date IS NOT NULL
                ");
                
                $this->pdo->exec("
                    UPDATE central_events 
                    SET end = CONCAT(event_date, ' ', COALESCE(event_time, '11:00:00'))
                    WHERE end IS NULL AND event_date IS NOT NULL
                ");
                
                // Drop old columns after migration
                $this->pdo->exec("ALTER TABLE central_events DROP COLUMN IF EXISTS event_date");
                $this->pdo->exec("ALTER TABLE central_events DROP COLUMN IF EXISTS event_time");
            }
        } catch (Exception $e) {
            // Migration failed, but continue
            error_log("Schema migration failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create or update an event in the central table
     */
    public function saveEvent($eventData) {
        try {
            // Convert event_date and event_time to start/end DATETIME
            $start = $eventData['event_date'] ?? $eventData['start'] ?? '';
            $end = $eventData['end'] ?? '';
            
            // If we have separate date and time, combine them
            if (isset($eventData['event_date']) && isset($eventData['event_time'])) {
                $start = $eventData['event_date'] . ' ' . $eventData['event_time'];
                $end = $eventData['event_date'] . ' ' . ($eventData['end_time'] ?? date('H:i:s', strtotime($eventData['event_time'] . ' +2 hours')));
            }
            
            // Determine status based on start date
            $startDate = new DateTime($start);
            $today = new DateTime();
            $status = $startDate < $today ? 'completed' : 'upcoming';
            
            // Check if event already exists (by title and start date)
            $stmt = $this->pdo->prepare("
                SELECT id FROM central_events 
                WHERE title = ? AND start = ?
            ");
            $stmt->execute([$eventData['title'], $start]);
            $existingEvent = $stmt->fetch();
            
            if ($existingEvent) {
                // Update existing event
                $stmt = $this->pdo->prepare("
                    UPDATE central_events 
                    SET description = ?, end = ?, location = ?, image_path = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([
                    $eventData['description'] ?? null,
                    $end,
                    $eventData['location'] ?? null,
                    $eventData['image_path'] ?? null,
                    $status,
                    $existingEvent['id']
                ]);
                
                return [
                    'success' => true,
                    'event' => [
                        'id' => 'event_' . (string)$existingEvent['id'],
                        'title' => $eventData['title'],
                        'start' => $start,
                        'end' => $end,
                        'location' => $eventData['location'] ?? ''
                    ],
                    'status' => $status,
                    'action' => 'updated'
                ];
            } else {
                // Insert new event
                $stmt = $this->pdo->prepare("
                    INSERT INTO central_events (title, description, start, end, location, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $eventData['title'],
                    $eventData['description'] ?? null,
                    $start,
                    $end,
                    $eventData['location'] ?? null,
                    $status
                ]);
                
                $eventId = $this->pdo->lastInsertId();
                
                return [
                    'success' => true,
                    'event' => [
                        'id' => 'event_' . (string)$eventId,
                        'title' => $eventData['title'],
                        'start' => $start,
                        'end' => $end,
                        'location' => $eventData['location'] ?? ''
                    ],
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
                ORDER BY start ASC
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
     * Get events for scheduler (upcoming events + recent completed events within 30 days)
     */
    public function getEventsForScheduler() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, description, start, end, location, status
                FROM central_events 
                WHERE status = 'upcoming' OR (status = 'completed' AND start >= DATE_SUB(NOW(), INTERVAL 30 DAY))
                ORDER BY start ASC
            ");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cast IDs as strings with event_ prefix
            foreach ($events as &$event) {
                $event['id'] = 'event_' . (string)$event['id'];
            }
            
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
                SELECT id, title, description, start, end, location, status
                FROM central_events 
                ORDER BY start DESC
            ");
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cast IDs as strings with event_ prefix
            foreach ($events as &$event) {
                $event['id'] = 'event_' . (string)$event['id'];
            }
            
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
            $now = date('Y-m-d H:i:s');
            
            // Update past events to completed
            $stmt = $this->pdo->prepare("
                UPDATE central_events 
                SET status = 'completed', updated_at = CURRENT_TIMESTAMP
                WHERE start < ? AND status = 'upcoming'
            ");
            $stmt->execute([$now]);
            $completedCount = $stmt->rowCount();
            
            // Update future events to upcoming
            $stmt = $this->pdo->prepare("
                UPDATE central_events 
                SET status = 'upcoming', updated_at = CURRENT_TIMESTAMP
                WHERE start >= ? AND status = 'completed'
            ");
            $stmt->execute([$now]);
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
