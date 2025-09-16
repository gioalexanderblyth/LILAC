<?php
/**
 * Events Manager Class
 * Handles all event-related database operations and business logic
 */

require_once 'config/database.php';

class EventsManager {
    private $pdo;
    
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->initializeTables();
    }
    
    /**
     * Initialize required database tables
     */
    private function initializeTables() {
        $this->createCentralEventsTable();
        $this->createEventTrashTable();
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
     * Create the event trash table
     */
    private function createEventTrashTable() {
        // Check if table exists first
        $tableExists = false;
        try {
            $checkTable = $this->pdo->prepare("SHOW TABLES LIKE 'event_trash'");
            $checkTable->execute();
            $tableExists = $checkTable->rowCount() > 0;
        } catch (Exception $e) {
            // Table check failed, proceed with creation
        }
        
        if (!$tableExists) {
            $createTableSQL = "CREATE TABLE event_trash (
                id INT AUTO_INCREMENT PRIMARY KEY,
                original_id INT,
                title VARCHAR(255),
                description TEXT,
                event_date DATE,
                event_time TIME,
                location VARCHAR(255),
                image_path VARCHAR(500),
                file_path VARCHAR(500),
                file_type VARCHAR(100),
                extracted_content TEXT,
                award_assignments TEXT,
                analysis_data TEXT,
                status VARCHAR(50),
                deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->pdo->exec($createTableSQL);
        }
    }
    
    /**
     * Migrate existing table schema to new format
     */
    private function migrateTableSchema() {
        // Check if start column exists
        $stmt = $this->pdo->prepare("SHOW COLUMNS FROM central_events LIKE 'start'");
        $stmt->execute();
        $startColumnExists = $stmt->rowCount() > 0;
        
        if (!$startColumnExists) {
            // Add start and end columns
            $this->pdo->exec("ALTER TABLE central_events ADD COLUMN start DATETIME AFTER description");
            $this->pdo->exec("ALTER TABLE central_events ADD COLUMN end DATETIME AFTER start");
            
            // Migrate existing data
            $stmt = $this->pdo->prepare("SELECT id, event_date, event_time FROM central_events WHERE event_date IS NOT NULL");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($events as $event) {
                $start = $event['event_date'] . ' ' . ($event['event_time'] ?: '09:00:00');
                $end = $event['event_date'] . ' ' . ($event['event_time'] ? date('H:i:s', strtotime($event['event_time'] . ' +2 hours')) : '11:00:00');
                
                $updateStmt = $this->pdo->prepare("UPDATE central_events SET start = ?, end = ? WHERE id = ?");
                $updateStmt->execute([$start, $end, $event['id']]);
            }
        }
    }
    
    /**
     * Load all events data
     */
    public function loadEventsData() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, description, start, end, location, image_path, status, created_at, updated_at
                FROM central_events 
                ORDER BY start ASC
            ");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group events by status
            $groupedEvents = [
                'upcoming' => [],
                'completed' => []
            ];
            
            foreach ($events as $event) {
                $groupedEvents[$event['status']][] = $event;
            }
            
            return [
                'success' => true,
                'data' => [
                    'events' => $groupedEvents,
                    'total' => count($events)
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => [
                    'events' => ['upcoming' => [], 'completed' => []],
                    'total' => 0
                ],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Load trash events data
     */
    public function loadTrashEventsData() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM event_trash ORDER BY deleted_at DESC");
            $stmt->execute();
            $trashEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'trash_events' => $trashEvents
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'trash_events' => [],
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
                SELECT id, title, description, start, end, location, image_path, status, created_at, updated_at
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
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a new event
     */
    public function createEvent($eventData) {
        try {
            // Convert event_date and event_time to start/end DATETIME
            $start = $eventData['event_date'] . ' ' . ($eventData['event_time'] ?: '09:00:00');
            $end = $eventData['event_date'] . ' ' . ($eventData['event_time'] ? date('H:i:s', strtotime($eventData['event_time'] . ' +2 hours')) : '11:00:00');
            
            // Determine status based on start date
            $startDate = new DateTime($start);
            $today = new DateTime();
            $status = $startDate < $today ? 'completed' : 'upcoming';
            
            $stmt = $this->pdo->prepare("
                INSERT INTO central_events (title, description, start, end, location, image_path, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $eventData['title'],
                $eventData['description'],
                $start,
                $end,
                $eventData['location'],
                $eventData['image_path'] ?? '',
                $status
            ]);
            
            $eventId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'event_id' => $eventId,
                'message' => 'Event created successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete event (move to trash)
     */
    public function deleteEvent($eventId) {
        try {
            // Get event data
            $event = $this->getEventById($eventId);
            if (!$event['success']) {
                return $event;
            }
            
            // Move to trash
            $stmt = $this->pdo->prepare("
                INSERT INTO event_trash (original_id, title, description, event_date, event_time, location, image_path, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $eventData = $event['event'];
            $stmt->execute([
                $eventId,
                $eventData['title'],
                $eventData['description'],
                date('Y-m-d', strtotime($eventData['start'])),
                date('H:i:s', strtotime($eventData['start'])),
                $eventData['location'],
                $eventData['image_path'],
                $eventData['status']
            ]);
            
            // Delete from main table
            $deleteStmt = $this->pdo->prepare("DELETE FROM central_events WHERE id = ?");
            $deleteStmt->execute([$eventId]);
            
            return [
                'success' => true,
                'message' => 'Event moved to trash successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Restore event from trash
     */
    public function restoreEvent($trashId) {
        try {
            // Get trash event data
            $stmt = $this->pdo->prepare("SELECT * FROM event_trash WHERE id = ?");
            $stmt->execute([$trashId]);
            $trashEvent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$trashEvent) {
                return [
                    'success' => false,
                    'error' => 'Trash event not found'
                ];
            }
            
            // Restore to main table
            $restoreStmt = $this->pdo->prepare("
                INSERT INTO central_events (title, description, start, end, location, image_path, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $start = $trashEvent['event_date'] . ' ' . ($trashEvent['event_time'] ?: '09:00:00');
            $end = $trashEvent['event_date'] . ' ' . ($trashEvent['event_time'] ? date('H:i:s', strtotime($trashEvent['event_time'] . ' +2 hours')) : '11:00:00');
            
            $restoreStmt->execute([
                $trashEvent['title'],
                $trashEvent['description'],
                $start,
                $end,
                $trashEvent['location'],
                $trashEvent['image_path'],
                $trashEvent['status']
            ]);
            
            // Delete from trash
            $deleteStmt = $this->pdo->prepare("DELETE FROM event_trash WHERE id = ?");
            $deleteStmt->execute([$trashId]);
            
            return [
                'success' => true,
                'message' => 'Event restored successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Permanently delete event from trash
     */
    public function permanentlyDeleteEvent($trashId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM event_trash WHERE id = ?");
            $stmt->execute([$trashId]);
            
            return [
                'success' => true,
                'message' => 'Event permanently deleted'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Empty trash
     */
    public function emptyTrash() {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM event_trash");
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'Trash emptied successfully'
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
