<?php
/**
 * Direct Event Creation Handler
 */

// Handle POST request for event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once 'classes/EventsManager.php';
        
        // Get form data
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $eventDate = $_POST['event_date'] ?? '';
        $eventTime = $_POST['event_time'] ?? null;
        $location = $_POST['location'] ?? '';
        $originalLink = $_POST['original_link'] ?? '';
        $awardType = $_POST['award_type'] ?? '';
        $imagePath = '';
        
        // Validate required fields
        if (empty($title)) {
            throw new Exception('Event title is required');
        }
        if (empty($eventDate)) {
            throw new Exception('Event date is required');
        }
        
        // Handle image upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/events/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = uniqid('event_', true) . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
                    $imagePath = $uploadPath;
                } else {
                    throw new Exception('Failed to upload image');
                }
            } else {
                throw new Exception('Invalid image file type. Allowed: ' . implode(', ', $allowedExtensions));
            }
        }
        
        // Initialize events manager
        $eventsManager = new EventsManager();
        
        // Prepare event data
        $eventData = [
            'title' => $title,
            'description' => $description,
            'event_date' => $eventDate,
            'event_time' => $eventTime,
            'location' => $location,
            'image_path' => $imagePath
        ];
        
        // Create event using the manager
        $result = $eventsManager->createEvent($eventData);
        
        if (!$result['success']) {
            throw new Exception($result['error']);
        }
        
        $eventId = $result['event_id'];
        
        // Redirect back to events page with success message
        $message = urlencode('Event "' . $title . '" created successfully');
        header("Location: events_activities.php?created=1&message=" . $message);
        exit;
        
    } catch (Exception $e) {
        // Redirect back with error message
        $message = urlencode('Error creating event: ' . $e->getMessage());
        header("Location: events_activities.php?error=1&message=" . $message);
        exit;
    }
} else {
    // Redirect back with error message
    $message = urlencode('Invalid request method');
    header("Location: events_activities.php?error=1&message=" . $message);
    exit;
}
?>
