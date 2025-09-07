<?php
// Simple migration to add sidebar_state to users table
require_once __DIR__ . '/../../config/database.php';

function respond($ok, $msg) {
	echo ($ok ? "[OK] " : "[ERR] ") . $msg . PHP_EOL;
}

try {
	$db = new Database();
	$pdo = $db->getConnection();

	// Ensure users table exists
	$pdo->exec("CREATE TABLE IF NOT EXISTS users (
		id INT AUTO_INCREMENT PRIMARY KEY,
		email VARCHAR(255) UNIQUE,
		password_hash VARCHAR(255) NULL,
		name VARCHAR(255) NULL
	)");
	respond(true, 'Ensured users table exists');

	// Add sidebar_state column if missing
	$hasCol = false;
	$stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'sidebar_state'");
	if ($stmt && $stmt->fetch()) { $hasCol = true; }
	if (!$hasCol) {
		$pdo->exec("ALTER TABLE users ADD COLUMN sidebar_state ENUM('open','closed') NOT NULL DEFAULT 'open'");
		respond(true, 'Added sidebar_state column');
	} else {
		respond(true, 'sidebar_state column already exists');
	}

	respond(true, 'Migration complete');
} catch (Throwable $e) {
	respond(false, 'Migration failed: ' . $e->getMessage());
}


