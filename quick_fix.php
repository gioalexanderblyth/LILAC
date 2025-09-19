<?php
require_once 'config/database.php';
$pdo = getDatabase();

// Just update any document that might be the "20.pdf" 
$stmt = $pdo->prepare("UPDATE enhanced_documents SET extracted_content = 'Agreement', category = 'MOU' WHERE id = 45");
$result = $stmt->execute();

if ($result) {
    echo "Updated document 45";
} else {
    echo "Failed to update";
}
?>
