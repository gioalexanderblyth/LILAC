<?php
// Lightweight MOUs/MOAs API with JSON storage

header('Content-Type: application/json');

$rootDir = dirname(__DIR__);
$dataDir = $rootDir . DIRECTORY_SEPARATOR . 'data';
$uploadsDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads';
$dbFile = $dataDir . DIRECTORY_SEPARATOR . 'mous.json';

// Ensure storage exists
if (!is_dir($dataDir)) { @mkdir($dataDir, 0777, true); }
if (!file_exists($dbFile)) { file_put_contents($dbFile, json_encode(['auto_id' => 1, 'mous' => []])); }

function respond_ok($payload = []) { echo json_encode(array_merge(['success' => true], $payload)); exit; }
function respond_err($message, $extra = []) { echo json_encode(array_merge(['success' => false, 'message' => $message], $extra)); exit; }

function load_db($dbFile) {
	$raw = @file_get_contents($dbFile);
	$data = json_decode($raw, true);
	if (!is_array($data)) { $data = ['auto_id' => 1, 'mous' => []]; }
	if (!isset($data['auto_id'])) { $data['auto_id'] = 1; }
	if (!isset($data['mous']) || !is_array($data['mous'])) { $data['mous'] = []; }
	return $data;
}

function save_db($dbFile, $data) { file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT)); }

function normalize_date($d) {
	if (!$d) return '';
	$ts = strtotime($d);
	if ($ts === false) return '';
	return date('Y-m-d', $ts);
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_all');

try {
	$db = load_db($dbFile);

	if ($action === 'add') {
		$partner = trim((string)($_POST['partner_name'] ?? $_POST['document_name'] ?? ''));
		$type = strtoupper(trim((string)($_POST['type'] ?? 'MOU')));
		$status = trim((string)($_POST['status'] ?? 'Active'));
		$dateSigned = normalize_date($_POST['date_signed'] ?? $_POST['signed_date'] ?? '');
		$endDate = normalize_date($_POST['end_date'] ?? $_POST['expiry_date'] ?? '');
		$description = (string)($_POST['description'] ?? '');
		$fileName = (string)($_POST['file_name'] ?? '');

		if ($partner === '') { respond_err('partner_name is required'); }
		if ($dateSigned === '') { $dateSigned = date('Y-m-d'); }
		if ($type !== 'MOU' && $type !== 'MOA') { $type = 'MOU'; }

		$id = $db['auto_id']++;
		$now = date('Y-m-d H:i:s');
		$record = [
			'id' => $id,
			'partner_name' => htmlspecialchars($partner, ENT_QUOTES, 'UTF-8'),
			'type' => $type,
			'status' => $status,
			'date_signed' => $dateSigned,
			'end_date' => $endDate ?: null,
			'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
			'file_name' => $fileName ?: null,
			'created_at' => $now,
			'updated_at' => $now
		];
		$db['mous'][] = $record;
		save_db($dbFile, $db);
		respond_ok(['mou' => $record]);
	}

	if ($action === 'update') {
		$id = intval($_POST['id'] ?? 0);
		if ($id <= 0) { respond_err('invalid id'); }
		$found = false;
		foreach ($db['mous'] as &$m) {
			if (intval($m['id']) === $id) {
				$found = true;
				if (isset($_POST['partner_name'])) { $m['partner_name'] = htmlspecialchars(trim((string)$_POST['partner_name']), ENT_QUOTES, 'UTF-8'); }
				if (isset($_POST['type'])) { $t = strtoupper(trim((string)$_POST['type'])); $m['type'] = ($t === 'MOA' ? 'MOA' : 'MOU'); }
				if (isset($_POST['status'])) { $m['status'] = trim((string)$_POST['status']); }
				if (isset($_POST['date_signed'])) { $m['date_signed'] = normalize_date($_POST['date_signed']); }
				if (isset($_POST['end_date'])) { $m['end_date'] = normalize_date($_POST['end_date']) ?: null; }
				if (isset($_POST['description'])) { $m['description'] = htmlspecialchars((string)$_POST['description'], ENT_QUOTES, 'UTF-8'); }
				if (isset($_POST['file_name'])) { $m['file_name'] = (string)$_POST['file_name'] ?: null; }
				$m['updated_at'] = date('Y-m-d H:i:s');
				break;
			}
		}
		unset($m);
		if (!$found) { respond_err('not found'); }
		save_db($dbFile, $db);
		respond_ok(['message' => 'updated']);
	}

	if ($action === 'delete') {
		$id = intval($_POST['id'] ?? 0);
		if ($id <= 0) { respond_err('invalid id'); }
		$idx = -1;
		foreach ($db['mous'] as $i => $m) { if (intval($m['id']) === $id) { $idx = $i; break; } }
		if ($idx === -1) { respond_err('not found'); }
		array_splice($db['mous'], $idx, 1);
		save_db($dbFile, $db);
		respond_ok(['message' => 'deleted']);
	}

	if ($action === 'get_all') {
		$search = trim((string)($_GET['search'] ?? ''));
		$status = trim((string)($_GET['status'] ?? ''));
		$type = trim((string)($_GET['type'] ?? ''));
		$mous = $db['mous'];
		if ($search !== '') {
			$q = mb_strtolower($search);
			$mous = array_values(array_filter($mous, function ($m) use ($q) {
				$hay = mb_strtolower(($m['partner_name'] ?? '') . ' ' . ($m['description'] ?? ''));
				return strpos($hay, $q) !== false;
			}));
		}
		if ($status !== '') { $mous = array_values(array_filter($mous, fn($m) => isset($m['status']) && $m['status'] === $status)); }
		if ($type !== '') { $mous = array_values(array_filter($mous, fn($m) => isset($m['type']) && $m['type'] === strtoupper($type))); }
		respond_ok(['mous' => $mous]);
	}

	if ($action === 'get_upcoming_expirations') {
		$days = max(1, intval($_GET['days'] ?? 30));
		$now = strtotime(date('Y-m-d'));
		$limitTs = strtotime("+{$days} days", $now);
		$items = array_values(array_filter($db['mous'], function ($m) use ($now, $limitTs) {
			if (empty($m['end_date'])) return false;
			$ts = strtotime($m['end_date']);
			if ($ts === false) return false;
			return $ts >= $now && $ts <= $limitTs;
		}));
		respond_ok(['mous' => $items]);
	}

	if ($action === 'get_stats') {
		$total = count($db['mous']);
		$active = 0; $expired = 0; $pending = 0; $expiringSoon = 0;
		$today = strtotime(date('Y-m-d'));
		$soon = strtotime('+30 days', $today);
		foreach ($db['mous'] as $m) {
			$st = $m['status'] ?? 'Active';
			if ($st === 'Active') $active++;
			if ($st === 'Expired') $expired++;
			if ($st === 'Pending') $pending++;
			if (!empty($m['end_date'])) {
				$ts = strtotime($m['end_date']);
				if ($ts !== false && $ts >= $today && $ts <= $soon) { $expiringSoon++; }
			}
		}
		respond_ok(['stats' => compact('total','active','expired','pending','expiringSoon')]);
	}

	if ($action === 'sync_from_documents') {
		// Check for MOU documents in the documents system that aren't in MOUs system
		$documentsFile = $dataDir . DIRECTORY_SEPARATOR . 'documents.json';
		if (!file_exists($documentsFile)) {
			respond_ok(['message' => 'No documents file found', 'synced' => 0]);
		}
		
		$documentsData = json_decode(file_get_contents($documentsFile), true);
		if (!isset($documentsData['documents']) || !is_array($documentsData['documents'])) {
			respond_ok(['message' => 'No documents found', 'synced' => 0]);
		}
		
		$synced = 0;
		$existingMous = array_column($db['mous'], 'document_id'); // Track by document_id to avoid duplicates
		
		foreach ($documentsData['documents'] as $doc) {
			// Check if this is a MOU document that should be in the MOUs system
			$category = $doc['category'] ?? '';
			$documentName = $doc['document_name'] ?? '';
			$filename = $doc['original_filename'] ?? '';
			
			// Check if it's categorized as MOU or has MOU keywords in name
			$isMou = ($category === 'MOUs & MOAs') || 
					preg_match('/\b(MOU|MOA|MEMORANDUM|AGREEMENT|PARTNERSHIP)\b/i', $documentName . ' ' . $filename);
			
			if ($isMou && !in_array($doc['id'], $existingMous)) {
				// Extract partner name from document name or filename
				$partner = $documentName;
				if (empty($partner)) {
					$partner = pathinfo($filename, PATHINFO_FILENAME);
				}
				
				// Clean up partner name
				$partner = preg_replace('/\b(MOU|MOA|MEMORANDUM|AGREEMENT|PARTNERSHIP|WITH|AND)\b/i', '', $partner);
				$partner = trim(preg_replace('/[_\-\s]+/', ' ', $partner));
				if (empty($partner)) $partner = 'Unknown Partner';
				
				// Determine type
				$type = 'MOU';
				if (preg_match('/\bMOA\b/i', $documentName . ' ' . $filename)) {
					$type = 'MOA';
				}
				
				// Add to MOUs system
				$mouId = $db['auto_id']++;
				$db['mous'][] = [
					'id' => $mouId,
					'document_id' => $doc['id'], // Link to original document
					'partner_name' => $partner,
					'type' => $type,
					'status' => 'Active',
					'date_signed' => '',
					'end_date' => '',
					'description' => $doc['description'] ?? '',
					'file_name' => $filename,
					'created_at' => $doc['upload_date'] ?? date('Y-m-d H:i:s')
				];
				$synced++;
			}
		}
		
		if ($synced > 0) {
			save_db($dbFile, $db);
		}
		
		respond_ok(['message' => "Synced {$synced} MOU documents", 'synced' => $synced]);
	}

	respond_err('Unknown action');
} catch (Throwable $e) {
	respond_err('Server error', ['error' => $e->getMessage()]);
} 