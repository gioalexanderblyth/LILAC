<?php
/**
 * MOU/MOA API with MySQL Database Storage
 * Provides secure CRUD operations for MOU/MOA documents
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database config and universal upload handler
require_once '../config/database.php';
require_once 'universal_upload_handler.php';

// Paths
$rootDir = dirname(__DIR__);
$uploadsDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads';

// Ensure uploads directory exists
if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0777, true); }

// Database connection
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    mous_respond(false, ['message' => 'Database connection failed: ' . $e->getMessage()]);
}

function mous_respond($ok, $payload = []) {
    echo json_encode(array_merge(['success' => $ok], $payload));
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_all');

if ($action === 'add') {
    // Get form data
    $institution = $_POST['institution'] ?? '';
    $location = $_POST['location'] ?? '';
    $contactDetails = $_POST['contact_details'] ?? '';
    $term = $_POST['term'] ?? '';
    $signDate = $_POST['sign_date'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $type = $_POST['type'] ?? 'MOU';
    
    // Handle file upload if present
    $fileName = '';
    $fileSize = 0;
    $filePath = '';
    
    if (isset($_FILES['mou-file']) && $_FILES['mou-file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = $_FILES['mou-file']['name'];
        $fileSize = $_FILES['mou-file']['size'];
        $filePath = 'uploads/' . $fileName;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['mou-file']['tmp_name'], $uploadDir . $fileName)) {
            mous_respond(false, ['message' => 'Failed to upload file']);
        }
    }
    
    // Insert into mous table
    try {
        $insertSql = "INSERT INTO mous (partner_name, status, date_signed, end_date, description, type, file_name, file_size, file_path, created_at, updated_at) 
                      VALUES (:partner_name, 'active', :date_signed, :end_date, :description, :type, :file_name, :file_size, :file_path, NOW(), NOW())";
        
        $description = "Institution: " . htmlspecialchars($institution, ENT_QUOTES, 'UTF-8') . 
                      "\nLocation: " . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . 
                      "\nContact Details: " . htmlspecialchars($contactDetails, ENT_QUOTES, 'UTF-8') . 
                      "\nTerm: " . htmlspecialchars($term, ENT_QUOTES, 'UTF-8') . 
                      "\nSign Date: " . $signDate . 
                      "\nStart Date: " . $startDate;
        
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->bindValue(':partner_name', htmlspecialchars($institution, ENT_QUOTES, 'UTF-8'));
        $insertStmt->bindValue(':date_signed', $signDate ?: null);
        $insertStmt->bindValue(':end_date', $endDate ?: null);
        $insertStmt->bindValue(':description', $description);
        $insertStmt->bindValue(':type', $type);
        $insertStmt->bindValue(':file_name', $fileName);
        $insertStmt->bindValue(':file_size', $fileSize);
        $insertStmt->bindValue(':file_path', $filePath);
        $insertStmt->execute();
        
        $mouId = $pdo->lastInsertId();
        
        // Return the result
        $mou = [
            'id' => $mouId,
            'institution' => htmlspecialchars($institution, ENT_QUOTES, 'UTF-8'),
            'location' => htmlspecialchars($location, ENT_QUOTES, 'UTF-8'),
            'contact_details' => htmlspecialchars($contactDetails, ENT_QUOTES, 'UTF-8'),
            'term' => htmlspecialchars($term, ENT_QUOTES, 'UTF-8'),
            'sign_date' => $signDate,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $type,
            'status' => 'active',
            'upload_date' => date('Y-m-d H:i:s'),
            'file_name' => $fileName,
            'file_path' => $filePath
        ];
        
        mous_respond(true, ['message' => 'MOU/MOA created successfully', 'mou' => $mou]);
        
    } catch (PDOException $e) {
        error_log("Error creating MOU record: " . $e->getMessage());
        mous_respond(false, ['message' => 'Failed to create MOU record: ' . $e->getMessage()]);
    }
}

if ($action === 'get_all' || $action === 'list') {
    try {
        // Get MOUs from the mous table
        $sql = "SELECT * FROM mous ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $mous = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Transform data to match expected format
        $transformedMous = [];
        foreach ($mous as $mou) {
            // Parse description to extract additional data
            $description = $mou['description'] ?? '';
            $data = [];
            
            if (!empty($description)) {
                $lines = explode("\n", $description);
                foreach ($lines as $line) {
                    if (strpos($line, ':') !== false) {
                        list($key, $value) = explode(':', $line, 2);
                        $data[trim($key)] = trim($value);
                    }
                }
            }
            
            $transformedMous[] = [
                'id' => $mou['id'],
                'institution' => $mou['partner_name'],
                'location' => $data['Location'] ?? '',
                'contact_details' => $data['Contact Details'] ?? '',
                'term' => $data['Term'] ?? '',
                'sign_date' => $mou['date_signed'],
                'start_date' => $data['Start Date'] ?? '',
                'end_date' => $mou['end_date'],
                'type' => $mou['type'],
                'status' => ucfirst($mou['status']),
                'upload_date' => $mou['created_at'],
                'file_name' => $mou['file_name'],
                'file_path' => $mou['file_path'],
                'description' => $description
            ];
        }
        
        mous_respond(true, ['mous' => $transformedMous, 'documents' => $transformedMous]);
    } catch (PDOException $e) {
        error_log("Database error in get_all: " . $e->getMessage());
        mous_respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'get_stats') {
    try {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                FROM mous";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        mous_respond(true, ['stats' => [
            'total' => intval($stats['total']),
            'active' => intval($stats['active']),
            'expired' => intval($stats['expired']),
            'pending' => intval($stats['pending']),
            'expiringSoon' => 0 // TODO: Implement expiration checking
        ]]);
    } catch (PDOException $e) {
        error_log("Database error in get_stats: " . $e->getMessage());
        mous_respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        mous_respond(false, ['message' => 'Invalid MOU ID']);
    }
    
    try {
        // Delete from mous table
        $sql = "DELETE FROM mous WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id);
        $result = $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            mous_respond(true, ['message' => 'MOU/MOA deleted successfully']);
        } else {
            mous_respond(false, ['message' => 'MOU/MOA not found']);
        }
    } catch (PDOException $e) {
        error_log("Database error in delete: " . $e->getMessage());
        mous_respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'update') {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        mous_respond(false, ['message' => 'Invalid MOU ID']);
    }
    
    // Get form data
    $institution = $_POST['institution'] ?? '';
    $location = $_POST['location'] ?? '';
    $contactDetails = $_POST['contact_details'] ?? '';
    $term = $_POST['term'] ?? '';
    $signDate = $_POST['sign_date'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $type = $_POST['type'] ?? 'MOU';
    
    // Handle file upload if present
    $fileName = '';
    $fileSize = 0;
    $filePath = '';
    
    if (isset($_FILES['mou-file']) && $_FILES['mou-file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = $_FILES['mou-file']['name'];
        $fileSize = $_FILES['mou-file']['size'];
        $filePath = 'uploads/' . $fileName;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['mou-file']['tmp_name'], $uploadDir . $fileName)) {
            mous_respond(false, ['message' => 'Failed to upload file']);
        }
    }
    
    try {
        // Build update query
        $updateFields = [];
        $params = [];
        
        $updateFields[] = "partner_name = :partner_name";
        $params[':partner_name'] = htmlspecialchars($institution, ENT_QUOTES, 'UTF-8');
        
        $updateFields[] = "date_signed = :date_signed";
        $params[':date_signed'] = $signDate ?: null;
        
        $updateFields[] = "end_date = :end_date";
        $params[':end_date'] = $endDate ?: null;
        
        $updateFields[] = "type = :type";
        $params[':type'] = $type;
        
        $updateFields[] = "updated_at = NOW()";
        
        // Update description with additional data
        $description = "Institution: " . htmlspecialchars($institution, ENT_QUOTES, 'UTF-8') . 
                      "\nLocation: " . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . 
                      "\nContact Details: " . htmlspecialchars($contactDetails, ENT_QUOTES, 'UTF-8') . 
                      "\nTerm: " . htmlspecialchars($term, ENT_QUOTES, 'UTF-8') . 
                      "\nSign Date: " . $signDate . 
                      "\nStart Date: " . $startDate;
        
        $updateFields[] = "description = :description";
        $params[':description'] = $description;
        
        // Update file info if new file uploaded
        if (!empty($fileName)) {
            $updateFields[] = "file_name = :file_name";
            $params[':file_name'] = $fileName;
            
            $updateFields[] = "file_size = :file_size";
            $params[':file_size'] = $fileSize;
            
            $updateFields[] = "file_path = :file_path";
            $params[':file_path'] = $filePath;
        }
        
        $params[':id'] = $id;
        
        $sql = "UPDATE mous SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            mous_respond(true, ['message' => 'MOU/MOA updated successfully']);
        } else {
            mous_respond(false, ['message' => 'MOU/MOA not found or no changes made']);
        }
        
    } catch (PDOException $e) {
        error_log("Error updating MOU record: " . $e->getMessage());
        mous_respond(false, ['message' => 'Failed to update MOU record: ' . $e->getMessage()]);
    }
}

if ($action === 'sync') {
    try {
        // Sync functionality - for now just return success with current count
        $sql = "SELECT COUNT(*) as total FROM mous";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $total = $stmt->fetchColumn();
        
        mous_respond(true, [
            'message' => 'Sync completed successfully',
            'synced_count' => intval($total)
        ]);
    } catch (PDOException $e) {
        error_log("Database error in sync: " . $e->getMessage());
        mous_respond(false, ['message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'update') {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        mous_respond(false, ['message' => 'Invalid MOU ID']);
    }
    
    // Get form data
    $institution = $_POST['institution'] ?? '';
    $location = $_POST['location'] ?? '';
    $contactDetails = $_POST['contact_details'] ?? '';
    $term = $_POST['term'] ?? '';
    $signDate = $_POST['sign_date'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $type = $_POST['type'] ?? 'MOU';
    
    // Handle file upload if present
    $fileName = '';
    $fileSize = 0;
    $filePath = '';
    
    if (isset($_FILES['mou-file']) && $_FILES['mou-file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = $_FILES['mou-file']['name'];
        $fileSize = $_FILES['mou-file']['size'];
        $filePath = 'uploads/' . $fileName;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['mou-file']['tmp_name'], $uploadDir . $fileName)) {
            mous_respond(false, ['message' => 'Failed to upload file']);
        }
    }
    
    try {
        // Build update query
        $updateFields = [];
        $params = [];
        
        $updateFields[] = "partner_name = :partner_name";
        $params[':partner_name'] = htmlspecialchars($institution, ENT_QUOTES, 'UTF-8');
        
        $updateFields[] = "date_signed = :date_signed";
        $params[':date_signed'] = $signDate ?: null;
        
        $updateFields[] = "end_date = :end_date";
        $params[':end_date'] = $endDate ?: null;
        
        $updateFields[] = "type = :type";
        $params[':type'] = $type;
        
        $updateFields[] = "updated_at = NOW()";
        
        // Update description with additional data
        $description = "Institution: " . htmlspecialchars($institution, ENT_QUOTES, 'UTF-8') . 
                      "\nLocation: " . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . 
                      "\nContact Details: " . htmlspecialchars($contactDetails, ENT_QUOTES, 'UTF-8') . 
                      "\nTerm: " . htmlspecialchars($term, ENT_QUOTES, 'UTF-8') . 
                      "\nSign Date: " . $signDate . 
                      "\nStart Date: " . $startDate;
        
        $updateFields[] = "description = :description";
        $params[':description'] = $description;
        
        // Update file info if new file uploaded
        if (!empty($fileName)) {
            $updateFields[] = "file_name = :file_name";
            $params[':file_name'] = $fileName;
            
            $updateFields[] = "file_size = :file_size";
            $params[':file_size'] = $fileSize;
            
            $updateFields[] = "file_path = :file_path";
            $params[':file_path'] = $filePath;
        }
        
        $params[':id'] = $id;
        
        $sql = "UPDATE mous SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            mous_respond(true, ['message' => 'MOU/MOA updated successfully']);
        } else {
            mous_respond(false, ['message' => 'MOU/MOA not found or no changes made']);
        }
        
    } catch (PDOException $e) {
        error_log("Error updating MOU record: " . $e->getMessage());
        mous_respond(false, ['message' => 'Failed to update MOU record: ' . $e->getMessage()]);
    }
}


// Default response
mous_respond(false, ['message' => 'Invalid action']);
?>
