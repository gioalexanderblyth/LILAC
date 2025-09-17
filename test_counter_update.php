<?php
require_once 'config/database.php';

$db = new Database();
$pdo = $db->getConnection();

// Manually trigger counter update
require_once 'api/documents.php';
updateAwardReadinessCounters($pdo);

echo "Counter update completed!";
?>