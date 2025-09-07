<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	echo json_encode(['status' => 'ok']);
	exit;
}

$awardsPath = __DIR__ . '/../data/awards.csv';
$recordsPath = __DIR__ . '/../data/records.csv';

$hasAwards = is_file($awardsPath) && filesize($awardsPath) > 0;
$hasRecords = is_file($recordsPath) && filesize($recordsPath) > 0;

echo json_encode([
	'has_awards' => $hasAwards,
	'has_records' => $hasRecords
]);


