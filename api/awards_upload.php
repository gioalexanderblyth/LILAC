<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	echo json_encode(['status' => 'ok']);
	exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function respond($data, $code = 200) {
	http_response_code($code);
	echo json_encode($data);
	exit;
}

if (!isset($_FILES['file'])) {
	respond(['error' => 'no_file_uploaded'], 400);
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
	respond(['error' => 'upload_failed', 'code' => $file['error']], 400);
}

$tmpPath = $file['tmp_name'];
$originalName = $file['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// Parse file into structured awards
$awards = [];

try {
	if ($ext === 'csv') {
		$fh = fopen($tmpPath, 'r');
		if ($fh === false) respond(['error' => 'cannot_open_file'], 400);
		$headers = fgetcsv($fh);
		if ($headers === false) respond(['error' => 'empty_file'], 400);
		$headers = array_map(function($h){ return strtolower(trim($h)); }, $headers);
		while (($row = fgetcsv($fh)) !== false) {
			$record = array_combine($headers, array_map('strval', $row));
			$awards[] = [
				'name' => trim($record['award name'] ?? $record['award_name'] ?? $record['name'] ?? ''),
				'description' => trim($record['description'] ?? ''),
				'category' => trim($record['category'] ?? ''),
				'requirements' => trim($record['requirements'] ?? $record['criteria'] ?? '')
			];
		}
		fclose($fh);
	} elseif (in_array($ext, ['xls', 'xlsx'])) {
		$reader = IOFactory::createReaderForFile($tmpPath);
		$spreadsheet = $reader->load($tmpPath);
		$sheet = $spreadsheet->getActiveSheet();
		$rows = $sheet->toArray(null, true, true, true);
		if (count($rows) === 0) respond(['error' => 'empty_file'], 400);
		$headerRow = array_shift($rows);
		$headers = array_map(function($h){ return strtolower(trim((string)$h)); }, array_values($headerRow));
		foreach ($rows as $row) {
			$values = array_values($row);
			$record = [];
			for ($i = 0; $i < count($headers); $i++) {
				$record[$headers[$i]] = (string)($values[$i] ?? '');
			}
			$awards[] = [
				'name' => trim($record['award name'] ?? $record['award_name'] ?? $record['name'] ?? ''),
				'description' => trim($record['description'] ?? ''),
				'category' => trim($record['category'] ?? ''),
				'requirements' => trim($record['requirements'] ?? $record['criteria'] ?? '')
			];
		}
	} elseif ($ext === 'json') {
		$text = file_get_contents($tmpPath);
		$data = json_decode($text, true);
		if (!is_array($data)) respond(['error' => 'invalid_json'], 400);
		foreach ($data as $item) {
			$awards[] = [
				'name' => trim((string)($item['name'] ?? $item['award_name'] ?? $item['award name'] ?? '')),
				'description' => trim((string)($item['description'] ?? '')),
				'category' => trim((string)($item['category'] ?? '')),
				'requirements' => trim((string)($item['requirements'] ?? $item['criteria'] ?? ''))
			];
		}
	} else {
		respond(['error' => 'unsupported_format', 'ext' => $ext], 400);
	}
} catch (Throwable $e) {
	respond(['error' => 'parse_failed', 'message' => $e->getMessage()], 500);
}

// Validate and filter
$valid = [];
$errors = [];
foreach ($awards as $idx => $a) {
	if ($a['name'] === '' || $a['requirements'] === '') {
		$errors[] = ['row' => $idx + 1, 'error' => 'missing_required_fields'];
		continue;
	}
	$valid[] = $a; // extra columns already ignored by mapping
}

if (empty($valid)) {
	respond(['error' => 'no_valid_rows', 'details' => $errors], 400);
}

// Save into database
$db = new Database();
$pdo = $db->getConnection();

// Ensure table exists (id, name, description, category, requirements, created_at)
$pdo->exec("CREATE TABLE IF NOT EXISTS uploaded_awards (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(255) NOT NULL,
	description TEXT NULL,
	category VARCHAR(100) NULL,
	requirements TEXT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $pdo->prepare("INSERT INTO uploaded_awards (name, description, category, requirements) VALUES (:name, :description, :category, :requirements)");
$inserted = 0;
foreach ($valid as $row) {
	$stmt->execute([
		':name' => $row['name'],
		':description' => $row['description'],
		':category' => $row['category'],
		':requirements' => $row['requirements']
	]);
	$inserted++;
}

respond(['ok' => true, 'inserted' => $inserted, 'errors' => $errors]);


