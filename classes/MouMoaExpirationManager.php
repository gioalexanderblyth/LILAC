<?php
/**
 * MOU/MOA Expiration Management Class
 * Handles expiration notifications and monitoring for MOU/MOA documents
 */
class MouMoaExpirationManager {
    
    private $dbFile;
    private $notificationDays = [30, 14, 7, 1]; // Days before expiration to notify
    
    public function __construct($dbFile) {
        $this->dbFile = $dbFile;
    }
    
    /**
     * Check for upcoming expirations and send notifications
     * @return array List of expiring MOUs with notification status
     */
    public function checkUpcomingExpirations() {
        $mous = $this->loadMous();
        $expiringMous = [];
        $today = new DateTime();
        
        foreach ($mous as $mou) {
            if (!isset($mou['end_date']) || empty($mou['end_date'])) {
                continue;
            }
            
            $endDate = new DateTime($mou['end_date']);
            $daysUntilExpiration = $today->diff($endDate)->days;
            
            // Check if MOU is expiring within notification periods
            foreach ($this->notificationDays as $notificationDay) {
                if ($daysUntilExpiration == $notificationDay) {
                    $expiringMous[] = [
                        'mou' => $mou,
                        'days_until_expiration' => $daysUntilExpiration,
                        'notification_sent' => $this->sendExpirationNotification($mou, $daysUntilExpiration)
                    ];
                    break; // Only send one notification per MOU
                }
            }
        }
        
        return $expiringMous;
    }
    
    /**
     * Get all expiring MOUs within specified days
     * @param int $days Number of days to check ahead
     * @return array List of expiring MOUs
     */
    public function getExpiringMous($days = 30) {
        $mous = $this->loadMous();
        $expiringMous = [];
        $today = new DateTime();
        $futureDate = clone $today;
        $futureDate->add(new DateInterval("P{$days}D"));
        
        foreach ($mous as $mou) {
            if (!isset($mou['end_date']) || empty($mou['end_date'])) {
                continue;
            }
            
            $endDate = new DateTime($mou['end_date']);
            
            // Check if MOU expires within the specified period
            if ($endDate >= $today && $endDate <= $futureDate) {
                $daysUntilExpiration = $today->diff($endDate)->days;
                $mou['days_until_expiration'] = $daysUntilExpiration;
                $mou['expiration_status'] = $this->getExpirationStatus($daysUntilExpiration);
                $expiringMous[] = $mou;
            }
        }
        
        // Sort by expiration date
        usort($expiringMous, function($a, $b) {
            return strtotime($a['end_date']) - strtotime($b['end_date']);
        });
        
        return $expiringMous;
    }
    
    /**
     * Get expiration status based on days remaining
     * @param int $daysRemaining Days until expiration
     * @return string Status string
     */
    private function getExpirationStatus($daysRemaining) {
        if ($daysRemaining <= 0) {
            return 'expired';
        } elseif ($daysRemaining <= 7) {
            return 'critical';
        } elseif ($daysRemaining <= 14) {
            return 'warning';
        } elseif ($daysRemaining <= 30) {
            return 'notice';
        } else {
            return 'active';
        }
    }
    
    /**
     * Send expiration notification
     * @param array $mou MOU data
     * @param int $daysRemaining Days until expiration
     * @return bool Success status
     */
    private function sendExpirationNotification($mou, $daysRemaining) {
        try {
            // Log notification (in production, this would send actual notifications)
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'mou_id' => $mou['id'],
                'title' => $mou['title'],
                'partner_name' => $mou['partner_name'],
                'end_date' => $mou['end_date'],
                'days_remaining' => $daysRemaining,
                'notification_type' => 'expiration_warning'
            ];
            
            $this->logNotification($logEntry);
            
            // In a real system, you would:
            // 1. Send email notifications
            // 2. Send in-app notifications
            // 3. Send SMS alerts
            // 4. Create calendar reminders
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to send expiration notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log notification for tracking
     * @param array $logEntry Notification log entry
     */
    private function logNotification($logEntry) {
        $logFile = dirname($this->dbFile) . DIRECTORY_SEPARATOR . 'mou_notifications.log';
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get notification statistics
     * @return array Notification statistics
     */
    public function getNotificationStats() {
        $logFile = dirname($this->dbFile) . DIRECTORY_SEPARATOR . 'mou_notifications.log';
        
        if (!file_exists($logFile)) {
            return [
                'total_notifications' => 0,
                'notifications_today' => 0,
                'notifications_this_week' => 0
            ];
        }
        
        $logs = file($logFile, FILE_IGNORE_NEW_LINES);
        $totalNotifications = count($logs);
        
        $today = date('Y-m-d');
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        
        $notificationsToday = 0;
        $notificationsThisWeek = 0;
        
        foreach ($logs as $log) {
            $entry = json_decode($log, true);
            if ($entry && isset($entry['timestamp'])) {
                $logDate = date('Y-m-d', strtotime($entry['timestamp']));
                
                if ($logDate === $today) {
                    $notificationsToday++;
                }
                
                if ($logDate >= $weekAgo) {
                    $notificationsThisWeek++;
                }
            }
        }
        
        return [
            'total_notifications' => $totalNotifications,
            'notifications_today' => $notificationsToday,
            'notifications_this_week' => $notificationsThisWeek
        ];
    }
    
    /**
     * Load MOUs from database
     * @return array MOU data
     */
    private function loadMous() {
        if (!file_exists($this->dbFile)) {
            return [];
        }
        
        $raw = file_get_contents($this->dbFile);
        $data = json_decode($raw, true);
        
        return isset($data['mous']) ? $data['mous'] : [];
    }
    
    /**
     * Schedule expiration checks (for cron job integration)
     * @return bool Success status
     */
    public function scheduleExpirationChecks() {
        // This method would integrate with a cron job system
        // For now, it just runs the check immediately
        
        $expiringMous = $this->checkUpcomingExpirations();
        
        // Log the scheduled check
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => 'scheduled_expiration_check',
            'expiring_mous_found' => count($expiringMous),
            'notifications_sent' => array_sum(array_column($expiringMous, 'notification_sent'))
        ];
        
        $this->logNotification($logEntry);
        
        return true;
    }
}
