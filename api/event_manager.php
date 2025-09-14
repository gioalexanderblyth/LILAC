<?php
/**
 * Event Management System
 * Handles event creation, counters, calendar integration, and status tracking
 */

class EventManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new event
     */
    public function createEvent($eventData) {
        try {
            // Validate required fields
            $this->validateEventData($eventData);
            
            // Determine event status based on date
            $status = $this->determineEventStatus($eventData['event_date']);
            
            // Auto-analyze location if not provided
            $location = $eventData['location'] ?? '';
            if (empty($location) && !empty($eventData['title'])) {
                $location = $this->suggestLocationFromContent($eventData['title'], $eventData['description'] ?? '');
            }
            
            // Insert event into database
            $stmt = $this->pdo->prepare("
                INSERT INTO enhanced_events 
                (title, description, event_date, event_time, location, original_link, status, extracted_content) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $content = $this->combineEventContent($eventData);
            
            $stmt->execute([
                $eventData['title'],
                $eventData['description'] ?? '',
                $eventData['event_date'],
                $eventData['event_time'] ?? null,
                $location,
                $eventData['original_link'] ?? '',
                $status,
                $content
            ]);
            
            $eventId = $this->pdo->lastInsertId();
            
            // Update counters
            $this->updateEventCounters();
            
            // Analyze and assign to awards if analyzer is available
            if (class_exists('AwardAnalyzer')) {
                $analyzer = new AwardAnalyzer($this->pdo);
                $analysis = $analyzer->analyzeContent($content, $eventData['title']);
                $assignments = $analyzer->determineAssignments($analysis);
                $analyzer->assignEvent($eventId, $assignments);
            }
            
            return [
                'success' => true,
                'event_id' => $eventId,
                'status' => $status,
                'content' => $content
            ];
            
        } catch (Exception $e) {
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
            // Validate required fields
            $this->validateEventData($eventData);
            
            // Determine new status based on date
            $status = $this->determineEventStatus($eventData['event_date']);
            
            // Update event in database
            $stmt = $this->pdo->prepare("
                UPDATE enhanced_events 
                SET title = ?, description = ?, event_date = ?, event_time = ?, 
                    location = ?, status = ?, extracted_content = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $content = $this->combineEventContent($eventData);
            
            $stmt->execute([
                $eventData['title'],
                $eventData['description'] ?? '',
                $eventData['event_date'],
                $eventData['event_time'] ?? null,
                $eventData['location'] ?? '',
                $status,
                $content,
                $eventId
            ]);
            
            // Update counters
            $this->updateEventCounters();
            
            // Re-analyze and reassign to awards if analyzer is available
            if (class_exists('AwardAnalyzer')) {
                $analyzer = new AwardAnalyzer($this->pdo);
                $analysis = $analyzer->analyzeContent($content, $eventData['title']);
                $assignments = $analyzer->determineAssignments($analysis);
                $analyzer->assignEvent($eventId, $assignments);
            }
            
            return [
                'success' => true,
                'event_id' => $eventId,
                'status' => $status
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
            // Delete event from database
            $stmt = $this->pdo->prepare("DELETE FROM enhanced_events WHERE id = ?");
            $stmt->execute([$eventId]);
            
            // Update counters
            $this->updateEventCounters();
            
            return [
                'success' => true,
                'message' => 'Event deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all events with optional filtering
     */
    public function getEvents($filters = []) {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $whereClause .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereClause .= " AND event_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= " AND event_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['award_key'])) {
            $whereClause .= " AND id IN (
                SELECT event_id FROM event_award_assignments WHERE award_key = ?
            )";
            $params[] = $filters['award_key'];
        }
        
        $orderBy = $filters['order_by'] ?? 'event_date DESC';
        $limit = $filters['limit'] ?? 100;
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM enhanced_events 
            {$whereClause} 
            ORDER BY {$orderBy} 
            LIMIT {$limit}
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get event by ID
     */
    public function getEventById($eventId) {
        $stmt = $this->pdo->prepare("SELECT * FROM enhanced_events WHERE id = ?");
        $stmt->execute([$eventId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Move event to trash instead of permanent deletion
     */
    public function moveEventToTrash($eventId) {
        try {
            // Get the event data first
            $event = $this->getEventById($eventId);
            if (!$event) {
                return ['success' => false, 'error' => 'Event not found'];
            }
            
            // Create trash table if it doesn't exist
            $this->createTrashTable();
            
            // Insert into trash table
            $stmt = $this->pdo->prepare("
                INSERT INTO event_trash 
                (original_id, title, description, event_date, event_time, location, image_path, file_path, file_type, extracted_content, award_assignments, analysis_data, status, deleted_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $event['id'],
                $event['title'],
                $event['description'],
                $event['event_date'],
                $event['event_time'],
                $event['location'],
                $event['image_path'],
                $event['file_path'],
                $event['file_type'],
                $event['extracted_content'],
                $event['award_assignments'],
                $event['analysis_data'],
                $event['status']
            ]);
            
            // Delete from main table
            $stmt = $this->pdo->prepare("DELETE FROM enhanced_events WHERE id = ?");
            $stmt->execute([$eventId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get events from trash
     */
    public function getTrashEvents() {
        try {
            $this->createTrashTable();
            $stmt = $this->pdo->query("SELECT * FROM event_trash ORDER BY deleted_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception('Failed to get trash events: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore event from trash
     */
    public function restoreEvent($trashId) {
        try {
            $this->createTrashTable();
            
            // Get the trash event
            $stmt = $this->pdo->prepare("SELECT * FROM event_trash WHERE id = ?");
            $stmt->execute([$trashId]);
            $trashEvent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$trashEvent) {
                return ['success' => false, 'error' => 'Trash event not found'];
            }
            
            // Insert back into main table with new ID
            $stmt = $this->pdo->prepare("
                INSERT INTO enhanced_events 
                (title, description, event_date, event_time, location, image_path, file_path, file_type, extracted_content, award_assignments, analysis_data, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $trashEvent['title'],
                $trashEvent['description'],
                $trashEvent['event_date'],
                $trashEvent['event_time'],
                $trashEvent['location'],
                $trashEvent['image_path'],
                $trashEvent['file_path'],
                $trashEvent['file_type'],
                $trashEvent['extracted_content'],
                $trashEvent['award_assignments'],
                $trashEvent['analysis_data'],
                $trashEvent['status']
            ]);
            
            // Delete from trash
            $stmt = $this->pdo->prepare("DELETE FROM event_trash WHERE id = ?");
            $stmt->execute([$trashId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Permanently delete event from trash
     */
    public function permanentlyDeleteEvent($trashId) {
        try {
            $this->createTrashTable();
            
            // Get file paths to delete files
            $stmt = $this->pdo->prepare("SELECT image_path, file_path FROM event_trash WHERE id = ?");
            $stmt->execute([$trashId]);
            $trashEvent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete files if they exist
            if ($trashEvent) {
                if ($trashEvent['image_path'] && file_exists($trashEvent['image_path'])) {
                    @unlink($trashEvent['image_path']);
                }
                if ($trashEvent['file_path'] && file_exists($trashEvent['file_path'])) {
                    @unlink($trashEvent['file_path']);
                }
            }
            
            // Delete from trash table
            $stmt = $this->pdo->prepare("DELETE FROM event_trash WHERE id = ?");
            $stmt->execute([$trashId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Empty all trash
     */
    public function emptyTrash() {
        try {
            $this->createTrashTable();
            
            // Get all file paths to delete files
            $stmt = $this->pdo->query("SELECT image_path, file_path FROM event_trash");
            $trashEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Delete all files
            foreach ($trashEvents as $event) {
                if ($event['image_path'] && file_exists($event['image_path'])) {
                    @unlink($event['image_path']);
                }
                if ($event['file_path'] && file_exists($event['file_path'])) {
                    @unlink($event['file_path']);
                }
            }
            
            // Clear trash table
            $this->pdo->exec("DELETE FROM event_trash");
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Create trash table if it doesn't exist
     */
    private function createTrashTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS event_trash (
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
                extracted_content LONGTEXT,
                award_assignments TEXT,
                analysis_data TEXT,
                status ENUM('upcoming','completed','cancelled'),
                deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);
    }
    
    /**
     * Get upcoming events
     */
    public function getUpcomingEvents($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM enhanced_events 
            WHERE event_date >= CURDATE() AND status = 'upcoming'
            ORDER BY event_date ASC, event_time ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get completed events
     */
    public function getCompletedEvents($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM enhanced_events 
            WHERE event_date < CURDATE() AND status = 'completed'
            ORDER BY event_date DESC, event_time DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get events for calendar view
     */
    public function getEventsForCalendar($startDate, $endDate) {
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                title,
                event_date,
                event_time,
                location,
                status,
                description
            FROM enhanced_events 
            WHERE event_date BETWEEN ? AND ?
            ORDER BY event_date ASC, event_time ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get event counters
     */
    public function getEventCounters() {
        $stmt = $this->pdo->query("
            SELECT counter_type, count_value, last_updated 
            FROM event_counters
        ");
        
        $counters = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $counters[$row['counter_type']] = [
                'count' => (int)$row['count_value'],
                'last_updated' => $row['last_updated']
            ];
        }
        
        return $counters;
    }
    
    /**
     * Update event counters
     */
    public function updateEventCounters() {
        // Count upcoming events
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM enhanced_events 
            WHERE event_date >= CURDATE() AND status = 'upcoming'
        ");
        $upcomingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count completed events
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count FROM enhanced_events 
            WHERE event_date < CURDATE() AND status = 'completed'
        ");
        $completedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count total events
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM enhanced_events");
        $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Update counters table
        $counters = [
            'upcoming' => $upcomingCount,
            'completed' => $completedCount,
            'total' => $totalCount
        ];
        
        foreach ($counters as $type => $count) {
            $stmt = $this->pdo->prepare("
                INSERT INTO event_counters (counter_type, count_value, last_updated) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                count_value = VALUES(count_value), 
                last_updated = NOW()
            ");
            $stmt->execute([$type, $count]);
        }
        
        return $counters;
    }
    
    /**
     * Update event statuses based on current date
     */
    public function updateEventStatuses() {
        // Update upcoming events that have passed
        $stmt = $this->pdo->prepare("
            UPDATE enhanced_events 
            SET status = 'completed' 
            WHERE event_date < CURDATE() AND status = 'upcoming'
        ");
        $stmt->execute();
        
        // Update counters
        $this->updateEventCounters();
        
        return true;
    }
    
    /**
     * Get event statistics
     */
    public function getEventStatistics() {
        $stats = [];
        
        // Total events by status
        $stmt = $this->pdo->query("
            SELECT status, COUNT(*) as count 
            FROM enhanced_events 
            GROUP BY status
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['by_status'][$row['status']] = (int)$row['count'];
        }
        
        // Events by month (last 12 months)
        $stmt = $this->pdo->query("
            SELECT 
                DATE_FORMAT(event_date, '%Y-%m') as month,
                COUNT(*) as count
            FROM enhanced_events 
            WHERE event_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(event_date, '%Y-%m')
            ORDER BY month ASC
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['by_month'][$row['month']] = (int)$row['count'];
        }
        
        // Events by award assignment
        $stmt = $this->pdo->query("
            SELECT 
                eaa.award_key,
                COUNT(*) as count
            FROM event_award_assignments eaa
            INNER JOIN enhanced_events e ON eaa.event_id = e.id
            GROUP BY eaa.award_key
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['by_award'][$row['award_key']] = (int)$row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Validate event data
     */
    private function validateEventData($eventData) {
        if (empty($eventData['title'])) {
            throw new Exception('Event title is required');
        }
        
        if (empty($eventData['event_date'])) {
            throw new Exception('Event date is required');
        }
        
        // Validate date format
        $date = DateTime::createFromFormat('Y-m-d', $eventData['event_date']);
        if (!$date || $date->format('Y-m-d') !== $eventData['event_date']) {
            throw new Exception('Invalid date format. Use YYYY-MM-DD');
        }
        
        // Validate time format if provided
        if (!empty($eventData['event_time'])) {
            $time = DateTime::createFromFormat('H:i:s', $eventData['event_time']);
            if (!$time || $time->format('H:i:s') !== $eventData['event_time']) {
                throw new Exception('Invalid time format. Use HH:MM:SS');
            }
        }
    }
    
    /**
     * Determine event status based on date
     */
    private function determineEventStatus($eventDate) {
        $today = date('Y-m-d');
        
        // Ensure we're comparing dates correctly
        $eventDateObj = new DateTime($eventDate);
        $todayObj = new DateTime($today);
        
        if ($eventDateObj < $todayObj) {
            return 'completed';
        } else {
            return 'upcoming';
        }
    }
    
    /**
     * Suggest location from content analysis
     */
    private function suggestLocationFromContent($title, $description) {
        $text = strtolower($title . ' ' . $description);
        
        // Check for virtual/online events
        if (preg_match('/\b(?:virtual|online|zoom|teams|webinar|web-based|remote|digital|live stream|streaming)\b/', $text)) {
            return 'Virtual/Online Event';
        }
        
        // Check for university/campus events
        if (preg_match('/\b(?:university|campus|college|school|institute|academic)\b/', $text)) {
            return 'University Campus';
        }
        
        // Check for conference events
        if (preg_match('/\b(?:conference|convention|summit|symposium|workshop|seminar)\b/', $text)) {
            return 'Conference Center';
        }
        
        // Check for cultural events
        if (preg_match('/\b(?:cultural|art|museum|gallery|theater|theatre|performance|exhibition)\b/', $text)) {
            return 'Cultural Venue';
        }
        
        // Check for specific location mentions
        if (preg_match('/\b(?:at|in|on|located at|held at|takes place at|venue|address|location|place|site|where|hosted by|hosted at)\s*:?\s*([^.!?\n,]{10,100})/i', $text, $matches)) {
            $location = trim($matches[1]);
            if (strlen($location) > 5 && strlen($location) < 100) {
                return $location;
            }
        }
        
        // Default suggestion
        return 'TBD (To Be Determined)';
    }
    
    /**
     * Combine event content for analysis
     */
    private function combineEventContent($eventData) {
        $content = '';
        
        if (!empty($eventData['title'])) {
            $content .= $eventData['title'] . ' ';
        }
        
        if (!empty($eventData['description'])) {
            $content .= $eventData['description'] . ' ';
        }
        
        if (!empty($eventData['location'])) {
            $content .= $eventData['location'] . ' ';
        }
        
        return trim($content);
    }
    
    /**
     * Generate event cards data
     */
    public function generateEventCards($events) {
        $cards = [];
        
        foreach ($events as $event) {
            $cards[] = [
                'id' => $event['id'],
                'title' => $event['title'],
                'date' => $event['event_date'],
                'time' => $event['event_time'],
                'location' => $event['location'],
                'description' => $event['description'],
                'status' => $event['status'],
                'image_path' => $event['image_path'],
                'file_path' => $event['file_path'],
                'formatted_date' => $this->formatEventDate($event['event_date'], $event['event_time']),
                'is_upcoming' => $event['status'] === 'upcoming',
                'is_completed' => $event['status'] === 'completed'
            ];
        }
        
        return $cards;
    }
    
    /**
     * Format event date and time for display
     */
    private function formatEventDate($date, $time = null) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        $formatted = $dateObj->format('M j, Y');
        
        if ($time) {
            $timeObj = DateTime::createFromFormat('H:i:s', $time);
            $formatted .= ' at ' . $timeObj->format('g:i A');
        }
        
        return $formatted;
    }
    
    /**
     * Get events assigned to specific awards
     */
    public function getEventsByAward($awardKey) {
        $stmt = $this->pdo->prepare("
            SELECT e.* FROM enhanced_events e
            INNER JOIN event_award_assignments eaa ON e.id = eaa.event_id
            WHERE eaa.award_key = ?
            ORDER BY e.event_date DESC
        ");
        $stmt->execute([$awardKey]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search events
     */
    public function searchEvents($query, $limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM enhanced_events 
            WHERE title LIKE ? OR description LIKE ? OR location LIKE ?
            ORDER BY event_date DESC
            LIMIT ?
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
