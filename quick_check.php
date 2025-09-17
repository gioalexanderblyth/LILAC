<?php
require_once 'config/database.php';
$db = new Database();
$pdo = $db->getConnection();

// Check award_readiness table
$stmt = $pdo->query("SELECT award_key, total_documents FROM award_readiness");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Award Readiness Counters:\n";
foreach ($results as $row) {
    echo $row['award_key'] . ": " . $row['total_documents'] . "\n";
}

// Check if test file has content
$stmt = $pdo->query("SELECT id, document_name, extracted_content FROM enhanced_documents WHERE id = 19");
$file = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nTest file content length: " . strlen($file['extracted_content']) . "\n";
?>
