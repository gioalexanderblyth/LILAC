<?php
// Lightweight Events API with JSON storage
// Similar to scheduler.php but specifically for events and activities

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$rootDir = dirname(__DIR__);
$dataDir = $rootDir . DIRECTORY_SEPARATOR . 'data';
$uploadsDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads';
$dbFile = $dataDir . DIRECTORY_SEPARATOR . 'events.json';

// Ensure storage exists
if (!is_dir($dataDir)) { @mkdir($dataDir, 0777, true); }
if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0777, true); }
if (!file_exists($dbFile)) { 
    file_put_contents($dbFile, json_encode([
        'auto_id' => 1, 
        'events' => [],
        'activities' => []
    ], JSON_PRETTY_PRINT)); 
}

function respond_ok($payload = []) { 
    echo json_encode(array_merge(['success' => true], $payload)); 
    exit; 
}

function respond_err($message, $extra = []) { 
    echo json_encode(array_merge(['success' => false, 'message' => $message], $extra)); 
    exit; 
}

function load_db($dbFile) {
    $raw = @file_get_contents($dbFile);
    $data = json_decode($raw, true);
    if (!is_array($data)) { 
        $data = ['auto_id' => 1, 'events' => [], 'activities' => []]; 
    }
    if (!isset($data['auto_id'])) { $data['auto_id'] = 1; }
    if (!isset($data['events']) || !is_array($data['events'])) { $data['events'] = []; }
    if (!isset($data['activities']) || !is_array($data['activities'])) { $data['activities'] = []; }
    return $data;
}

function save_db($dbFile, $data) { 
    file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT)); 
}

function normalize_date($d) {
    if (!$d) return date('Y-m-d');
    $ts = strtotime($d);
    if ($ts === false) return date('Y-m-d');
    return date('Y-m-d', $ts);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_all');

try {
    $db = load_db($dbFile);

    switch ($action) {
        case 'add':
            $name = trim((string)($_POST['name'] ?? ''));
            $organizer = trim((string)($_POST['organizer'] ?? ''));
            $place = trim((string)($_POST['place'] ?? ''));
            $date = normalize_date($_POST['date'] ?? '');
            $status = trim((string)($_POST['status'] ?? 'upcoming'));
            $type = trim((string)($_POST['type'] ?? 'activities'));
            $description = trim((string)($_POST['description'] ?? ''));
            $image_file = trim((string)($_POST['image_file'] ?? ''));
            $ocr_text = trim((string)($_POST['ocr_text'] ?? ''));
            $confidence = (float)($_POST['confidence'] ?? 0);

            if ($name === '') { 
                respond_err('Event name is required'); 
            }

            // Handle file upload if present
            $saved_file_path = '';
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $originalName = $_FILES['file']['name'];
                $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
                
                // Generate unique filename
                $unique = uniqid('event_', true) . ($ext ? ('.' . $ext) : '');
                $dest = $uploadsDir . DIRECTORY_SEPARATOR . $unique;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                    $saved_file_path = $unique;
                    $image_file = $originalName; // Store original name for reference
                } else {
                    respond_err('Failed to save uploaded file');
                }
            }

            $newEvent = [
                'id' => $db['auto_id'],
                'name' => $name,
                'organizer' => $organizer,
                'place' => $place,
                'date' => $date,
                'status' => $status,
                'type' => $type,
                'description' => $description,
                'image_file' => $image_file,
                'saved_file_path' => $saved_file_path,
                'ocr_text' => $ocr_text,
                'confidence' => $confidence,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Add to the appropriate array based on type
            if ($type === 'events') {
                $db['events'][] = $newEvent;
            } else {
                $db['activities'][] = $newEvent;
            }

            $db['auto_id']++;
            save_db($dbFile, $db);
            respond_ok(['event' => $newEvent]);
            break;

        case 'get_all':
            // Return both events and activities
            $allItems = array_merge($db['events'], $db['activities']);
            // Sort by created_at descending (newest first)
            usort($allItems, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            respond_ok(['events' => $allItems]);
            break;

        case 'get_by_type':
            $type = $_GET['type'] ?? $_POST['type'] ?? 'all';
            if ($type === 'all') {
                $items = array_merge($db['events'], $db['activities']);
            } else {
                $items = $db[$type] ?? [];
            }
            // Sort by created_at descending
            usort($items, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            respond_ok(['events' => $items]);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) { 
                respond_err('Valid ID is required'); 
            }

            $found = false;
            $deletedEvent = null;
            
            // Check events array
            foreach ($db['events'] as $key => $event) {
                if ($event['id'] == $id) {
                    $deletedEvent = $event;
                    unset($db['events'][$key]);
                    $db['events'] = array_values($db['events']); // Re-index
                    $found = true;
                    break;
                }
            }
            
            // Check activities array if not found in events
            if (!$found) {
                foreach ($db['activities'] as $key => $activity) {
                    if ($activity['id'] == $id) {
                        $deletedEvent = $activity;
                        unset($db['activities'][$key]);
                        $db['activities'] = array_values($db['activities']); // Re-index
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) { 
                respond_err('Event not found'); 
            }

            // Delete associated file if it exists
            if ($deletedEvent && isset($deletedEvent['saved_file_path']) && $deletedEvent['saved_file_path']) {
                $filePath = $uploadsDir . DIRECTORY_SEPARATOR . $deletedEvent['saved_file_path'];
                if (file_exists($filePath)) {
                    if (unlink($filePath)) {
                        $fileDeleted = true;
                    } else {
                        // Log warning but don't fail the deletion
                        error_log("Warning: Could not delete file: " . $filePath);
                        $fileDeleted = false;
                    }
                } else {
                    $fileDeleted = false; // File didn't exist
                }
            } else {
                $fileDeleted = null; // No file to delete
            }

            save_db($dbFile, $db);
            
            $response = ['message' => 'Event deleted successfully'];
            if ($fileDeleted === true) {
                $response['file_deleted'] = true;
            } elseif ($fileDeleted === false) {
                $response['file_deleted'] = false;
                $response['file_warning'] = 'Associated file could not be deleted';
            }
            
            respond_ok($response);
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { 
                respond_err('Valid ID is required'); 
            }

            $found = false;
            $updatedEvent = null;

            // Check events array
            foreach ($db['events'] as $key => $event) {
                if ($event['id'] == $id) {
                    $db['events'][$key]['name'] = trim((string)($_POST['name'] ?? $event['name']));
                    $db['events'][$key]['organizer'] = trim((string)($_POST['organizer'] ?? $event['organizer']));
                    $db['events'][$key]['place'] = trim((string)($_POST['place'] ?? $event['place']));
                    $db['events'][$key]['date'] = normalize_date($_POST['date'] ?? $event['date']);
                    $db['events'][$key]['status'] = trim((string)($_POST['status'] ?? $event['status']));
                    $db['events'][$key]['description'] = trim((string)($_POST['description'] ?? $event['description']));
                    $db['events'][$key]['updated_at'] = date('Y-m-d H:i:s');
                    $updatedEvent = $db['events'][$key];
                    $found = true;
                    break;
                }
            }
            
            // Check activities array if not found in events
            if (!$found) {
                foreach ($db['activities'] as $key => $activity) {
                    if ($activity['id'] == $id) {
                        $db['activities'][$key]['name'] = trim((string)($_POST['name'] ?? $activity['name']));
                        $db['activities'][$key]['organizer'] = trim((string)($_POST['organizer'] ?? $activity['organizer']));
                        $db['activities'][$key]['place'] = trim((string)($_POST['place'] ?? $activity['place']));
                        $db['activities'][$key]['date'] = normalize_date($_POST['date'] ?? $activity['date']);
                        $db['activities'][$key]['status'] = trim((string)($_POST['status'] ?? $activity['status']));
                        $db['activities'][$key]['description'] = trim((string)($_POST['description'] ?? $activity['description']));
                        $db['activities'][$key]['updated_at'] = date('Y-m-d H:i:s');
                        $updatedEvent = $db['activities'][$key];
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) { 
                respond_err('Event not found'); 
            }

            save_db($dbFile, $db);
            respond_ok(['event' => $updatedEvent]);
            break;

        default:
            respond_err('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    respond_err('Server error: ' . $e->getMessage());
}
?> 