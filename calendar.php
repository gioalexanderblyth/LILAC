<?php
// LILAC Calendar - Comprehensive Calendar View
require_once 'config/database.php';

function loadCalendarData() {
    try {
        // Load meetings from JSON file
        $DATA_DIR = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data';
        $DATA_FILE = $DATA_DIR . DIRECTORY_SEPARATOR . 'meetings.json';
        
        if (!is_dir($DATA_DIR)) {
            @mkdir($DATA_DIR, 0777, true);
        }
        if (!file_exists($DATA_FILE)) {
            @file_put_contents($DATA_FILE, json_encode([ 'next_id' => 1, 'meetings' => [], 'trash' => [] ], JSON_PRETTY_PRINT));
        }
        
        $raw = @file_get_contents($DATA_FILE);
        if ($raw === false || trim($raw) === '') {
            $meetings = [];
        } else {
            $data = json_decode($raw, true);
            $meetings = isset($data['meetings']) && is_array($data['meetings']) ? $data['meetings'] : [];
        }
        
        // Load events from central_events table
        $events = [];
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("
                SELECT id, title, description, start, end, location, status
                FROM central_events 
                WHERE status = 'upcoming' OR (status = 'completed' AND start >= DATE_SUB(NOW(), INTERVAL 30 DAY))
                ORDER BY start ASC
            ");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // Database connection failed, use empty events
            $events = [];
        }
        
        // Combine and format data
        $allItems = [];
        
        // Add meetings
        foreach ($meetings as $m) {
            $allItems[] = [
                'id' => $m['id'],
                'title' => $m['title'],
                'meeting_date' => $m['date'],
                'meeting_time' => $m['time'],
                'end_date' => $m['end_date'] ?? $m['date'],
                'end_time' => $m['end_time'] ?? $m['time'],
                'description' => isset($m['description']) ? $m['description'] : '',
                'is_all_day' => isset($m['is_all_day']) ? $m['is_all_day'] : '0',
                'color' => isset($m['color']) ? $m['color'] : 'blue',
                'organizer' => isset($m['organizer']) ? $m['organizer'] : '',
                'venue' => isset($m['venue']) ? $m['venue'] : '',
                'location' => isset($m['venue']) ? $m['venue'] : '',
                'type' => 'meeting'
            ];
        }
        
        // Add events
        foreach ($events as $e) {
            $startDateTime = new DateTime($e['start']);
            $endDateTime = $e['end'] ? new DateTime($e['end']) : clone $startDateTime->modify('+2 hours');
            
            $allItems[] = [
                'id' => 'event_' . (string)$e['id'],
                'title' => $e['title'],
                'meeting_date' => $startDateTime->format('Y-m-d'),
                'meeting_time' => $startDateTime->format('H:i'),
                'end_date' => $endDateTime->format('Y-m-d'),
                'end_time' => $endDateTime->format('H:i'),
                'description' => $e['description'] ?: '',
                'is_all_day' => '0',
                'color' => 'green',
                'organizer' => 'LILAC',
                'venue' => $e['location'] ?: '',
                'location' => $e['location'] ?: '',
                'type' => 'event'
            ];
        }
        
        return $allItems;
    } catch (Exception $e) {
        return [];
    }
}

$calendarData = loadCalendarData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LILAC Calendar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="modern-design-system.css">
    <link rel="stylesheet" href="dashboard-theme.css">
    <link rel="stylesheet" href="sidebar-enhanced.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>(function(){ try { document.documentElement.classList.add('sidebar-prep'); } catch(e){} })();</script>
    <style id="sidebar-prep-style">.sidebar-prep #sidebar, .sidebar-prep nav.modern-nav, .sidebar-prep #main-content{ transition: none !important; }</style>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {}
            }
        }
    </script>
    <style>
        /* Typography */
        body { font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", sans-serif; }

        /* Calendar Styles */
        .calendar-day {
            min-height: 120px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .calendar-day:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
        }
        
        .calendar-day.today {
            background-color: #eff6ff;
            border-color: #3b82f6;
        }
        
        .calendar-day.other-month {
            background-color: #f9fafb;
            color: #9ca3af;
        }
        
        .calendar-day.selected {
            background-color: #dbeafe;
            border-color: #2563eb;
            border-width: 2px;
        }

        .event-item {
            font-size: 10px;
            padding: 2px 4px;
            margin: 1px 0;
            border-radius: 3px;
            cursor: pointer;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .event-item.meeting {
            background-color: #dbeafe;
            color: #1e40af;
            border-left: 3px solid #3b82f6;
        }
        
        .event-item.event {
            background-color: #dcfce7;
            color: #166534;
            border-left: 3px solid #16a34a;
        }
        
        .view-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .calendar-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Custom scrollbar for event details */
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
    <script src="connection-status.js"></script>
    <script src="lilac-enhancements.js?v=1.1"></script>
</head>
<body class="bg-gray-50">
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
