<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDatabase();
        
        // Update document 45 with Agreement content
        $stmt = $pdo->prepare("UPDATE enhanced_documents SET extracted_content = 'Agreement', category = 'MOU' WHERE id = 45");
        $result = $stmt->execute();
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Document 45 updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update document']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
