<?php
/**
 * Universal Upload API
 * Handles all file uploads through the centralized system
 */

require_once 'universal_upload_handler.php';

// Set JSON header
header('Content-Type: application/json');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Helper function for API responses
function api_respond($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit;
}

try {
    $uploadHandler = new UniversalUploadHandler();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload_file':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_respond(false, ['message' => 'Method not allowed'], 405);
            }
            
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                api_respond(false, ['message' => 'No file uploaded'], 400);
            }
            
            $uploadedBy = $_POST['uploaded_by'] ?? 'system';
            $sourcePage = $_POST['source_page'] ?? 'docs';
            
            $result = $uploadHandler->handleUpload($_FILES['file'], $uploadedBy, $sourcePage);
            
            if ($result['success']) {
                api_respond(true, [
                    'file_id' => $result['file_id'],
                    'category' => $result['category'],
                    'file_path' => $result['file_path'],
                    'linked_pages' => $result['linked_pages'],
                    'event_date' => $result['event_date'],
                    'message' => 'File uploaded and categorized successfully'
                ]);
            } else {
                api_respond(false, ['message' => $result['error']], 400);
            }
            break;
            
        case 'get_files':
            $category = $_GET['category'] ?? null;
            $status = $_GET['status'] ?? 'active';
            
            $files = $uploadHandler->getFilesByCategory($category, $status);
            api_respond(true, ['files' => $files]);
            break;
            
        case 'get_file':
            $fileId = $_GET['file_id'] ?? '';
            if (empty($fileId)) {
                api_respond(false, ['message' => 'File ID is required'], 400);
            }
            
            $file = $uploadHandler->getFileById($fileId);
            if ($file) {
                api_respond(true, ['file' => $file]);
            } else {
                api_respond(false, ['message' => 'File not found'], 404);
            }
            break;
            
        case 'update_category':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_respond(false, ['message' => 'Method not allowed'], 405);
            }
            
            $fileId = $_POST['file_id'] ?? '';
            $newCategory = $_POST['category'] ?? '';
            
            if (empty($fileId) || empty($newCategory)) {
                api_respond(false, ['message' => 'File ID and category are required'], 400);
            }
            
            $result = $uploadHandler->updateFileCategory($fileId, $newCategory);
            
            if ($result['success']) {
                api_respond(true, ['message' => 'Category updated successfully']);
            } else {
                api_respond(false, ['message' => $result['error']], 400);
            }
            break;
            
        case 'delete_file':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_respond(false, ['message' => 'Method not allowed'], 405);
            }
            
            $fileId = $_POST['file_id'] ?? '';
            if (empty($fileId)) {
                api_respond(false, ['message' => 'File ID is required'], 400);
            }
            
            $result = $uploadHandler->deleteFile($fileId);
            
            if ($result['success']) {
                api_respond(true, ['message' => 'File deleted successfully']);
            } else {
                api_respond(false, ['message' => $result['error']], 400);
            }
            break;
            
        case 'get_categories':
            api_respond(true, [
                'categories' => ['events', 'mou', 'awards', 'templates'],
                'allowed_types' => ['pdf', 'docx', 'txt', 'jpg', 'jpeg', 'png'],
                'max_size' => '10MB'
            ]);
            break;
            
        default:
            api_respond(false, ['message' => 'Invalid action'], 400);
            break;
    }
    
} catch (Exception $e) {
    api_respond(false, ['message' => 'Server error: ' . $e->getMessage()], 500);
}
?>
