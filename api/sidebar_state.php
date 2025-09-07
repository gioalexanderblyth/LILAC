<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	echo json_encode(['ok' => true]);
	exit;
}

require_once __DIR__ . '/../config/database.php';

function getUserId() {
	// For demo purposes, use a fixed user id 1. Replace with real auth.
	return 1;
}

try {
	$db = new Database();
	$pdo = $db->getConnection();

	$userId = getUserId();

	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$stmt = $pdo->prepare('SELECT sidebar_state FROM users WHERE id = :id');
		$stmt->execute([':id' => $userId]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$state = $row && !empty($row['sidebar_state']) ? $row['sidebar_state'] : 'open';
		echo json_encode(['ok' => true, 'sidebar_state' => $state]);
		exit;
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$raw = file_get_contents('php://input');
		$data = json_decode($raw, true);
		$state = isset($data['sidebar_state']) ? $data['sidebar_state'] : null;
		if (!in_array($state, ['open','closed'], true)) {
			echo json_encode(['ok' => false, 'error' => 'invalid_state']);
			exit;
		}
		// Ensure user row exists
		$pdo->prepare('INSERT IGNORE INTO users (id, email) VALUES (:id, NULL)')->execute([':id' => $userId]);
		$stmt = $pdo->prepare('UPDATE users SET sidebar_state = :state WHERE id = :id');
		$stmt->execute([':state' => $state, ':id' => $userId]);
		echo json_encode(['ok' => true]);
		exit;
	}

	echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
} catch (Throwable $e) {
	echo json_encode(['ok' => false, 'error' => 'exception', 'message' => $e->getMessage()]);
}


