<?php
header('Content-Type: application/json');

$emailLogFile = 'email_log.txt';

if (file_exists($emailLogFile)) {
    if (unlink($emailLogFile)) {
        echo json_encode(['success' => true, 'message' => 'Email log cleared successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear email log']);
    }
} else {
    echo json_encode(['success' => true, 'message' => 'Email log was already empty']);
}
?> 