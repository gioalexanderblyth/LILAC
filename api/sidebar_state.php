<?php
/**
 * Sidebar State API
 * Handles user sidebar state synchronization between localStorage and database
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Create user_sidebar_state table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS user_sidebar_state (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(100) NOT NULL,
        state ENUM('open', 'closed') NOT NULL DEFAULT 'closed',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user (user_id)
    )";
    $pdo->exec($createTableSQL);
    
    // Get user ID (for now, using session or default to 'default_user')
    $userId = $_SESSION['user_id'] ?? $_POST['user_id'] ?? $_GET['user_id'] ?? 'default_user';
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_state':
            // Get current state from database
            $stmt = $pdo->prepare("SELECT state, updated_at FROM user_sidebar_state WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                api_respond(true, [
                    'state' => $result['state'],
                    'updated_at' => $result['updated_at'],
                    'synced' => true
                ]);
            } else {
                // No state found, return default
                api_respond(true, [
                    'state' => 'closed',
                    'updated_at' => null,
                    'synced' => false
                ]);
            }
            break;
            
        case 'save_state':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                api_respond(false, ['message' => 'Method not allowed'], 405);
            }
            
            $state = $_POST['state'] ?? $_POST['sidebar_state'] ?? '';
            if (!in_array($state, ['open', 'closed'])) {
                api_respond(false, ['message' => 'Invalid state. Must be "open" or "closed"'], 400);
            }
            
            // Insert or update state
            $stmt = $pdo->prepare("
                INSERT INTO user_sidebar_state (user_id, state) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE 
                state = VALUES(state), 
                updated_at = CURRENT_TIMESTAMP
            ");
            
            if ($stmt->execute([$userId, $state])) {
                api_respond(true, [
                    'state' => $state,
                    'synced' => true,
                    'message' => 'State saved successfully'
                ]);
            } else {
                api_respond(false, ['message' => 'Failed to save state'], 500);
            }
            break;
            
        case 'sync_state':
            // Sync localStorage state with database
            $localState = $_POST['local_state'] ?? '';
            $lastSync = $_POST['last_sync'] ?? null;
            
            if (!in_array($localState, ['open', 'closed'])) {
                api_respond(false, ['message' => 'Invalid local state'], 400);
            }
            
            // Get current database state
            $stmt = $pdo->prepare("SELECT state, updated_at FROM user_sidebar_state WHERE user_id = ?");
            $stmt->execute([$userId]);
            $dbResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$dbResult) {
                // No database state, save local state
                $stmt = $pdo->prepare("INSERT INTO user_sidebar_state (user_id, state) VALUES (?, ?)");
                $stmt->execute([$userId, $localState]);
                
                api_respond(true, [
                    'state' => $localState,
                    'synced' => true,
                    'action' => 'saved_local'
                ]);
            } else {
                $dbState = $dbResult['state'];
                $dbUpdated = $dbResult['updated_at'];
                
                // Compare timestamps if provided
                if ($lastSync && $dbUpdated > $lastSync) {
                    // Database is newer, return database state
                    api_respond(true, [
                        'state' => $dbState,
                        'synced' => true,
                        'action' => 'use_database',
                        'updated_at' => $dbUpdated
                    ]);
                } else if ($localState !== $dbState) {
                    // States differ, update database with local state
                    $stmt = $pdo->prepare("
                        UPDATE user_sidebar_state 
                        SET state = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$localState, $userId]);
                    
                    api_respond(true, [
                        'state' => $localState,
                        'synced' => true,
                        'action' => 'updated_database'
                    ]);
                } else {
                    // States are the same
                    api_respond(true, [
                        'state' => $localState,
                        'synced' => true,
                        'action' => 'already_synced'
                    ]);
                }
            }
            break;
            
        default:
            api_respond(false, ['message' => 'Invalid action'], 400);
            break;
    }
    
} catch (Exception $e) {
    api_respond(false, ['message' => 'Server error: ' . $e->getMessage()], 500);
}
?>