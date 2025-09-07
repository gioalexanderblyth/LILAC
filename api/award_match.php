<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	echo json_encode(['status' => 'ok']);
	exit;
}

function read_json_body() {
	$raw = file_get_contents('php://input');
	if (!$raw) return null;
	$decoded = json_decode($raw, true);
	return is_array($decoded) ? $decoded : null;
}

function php_jaccard_similarity($setA, $setB) {
	$setA = array_values(array_unique(array_map('strval', $setA ?? [])));
	$setB = array_values(array_unique(array_map('strval', $setB ?? [])));
	if (count($setA) === 0 && count($setB) === 0) return 1.0;
	if (count($setA) === 0 || count($setB) === 0) return 0.0;
	$setA_map = array_fill_keys($setA, true);
	$setB_map = array_fill_keys($setB, true);
	$intersection = 0;
	foreach ($setA_map as $k => $_) {
		if (isset($setB_map[$k])) $intersection++;
	}
	$union = count($setA_map) + count($setB_map) - $intersection;
	return $union > 0 ? $intersection / $union : 1.0;
}

function run_python_jaccard($pairsOrSingle) {
	$python = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
	$script = __DIR__ . '/../scripts/jaccard_similarity.py';
	$payload = json_encode($pairsOrSingle);
	$descriptorspec = [
		0 => ['pipe', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w']
	];
	$process = @proc_open("$python \"$script\"", $descriptorspec, $pipes, null, null, ['bypass_shell' => true]);
	if (!is_resource($process)) return null;
	try {
		fwrite($pipes[0], $payload);
		fclose($pipes[0]);
		$out = stream_get_contents($pipes[1]);
		$err = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$code = proc_close($process);
		if ($code !== 0) return null;
		$decoded = json_decode($out, true);
		return is_array($decoded) ? $decoded : null;
	} catch (Throwable $e) {
		return null;
	}
}

$body = read_json_body();
if (!$body) {
	echo json_encode(['error' => 'invalid_json']);
	exit;
}

// Expected input:
// {
//   "awardCriteria": { "leadership": [..], "education": [..], ... },
//   "activities": { "leadership": [..], "education": [..], ... }
// }

$awardCriteria = isset($body['awardCriteria']) && is_array($body['awardCriteria']) ? $body['awardCriteria'] : [];
$activities = isset($body['activities']) && is_array($body['activities']) ? $body['activities'] : [];

// Optional: fetch uploaded awards from DB to compute per-award matches
require_once __DIR__ . '/../config/database.php';
$uploadedAwards = [];
if (class_exists('Database')) {
	try {
		$db = new Database();
		$pdo = $db->getConnection();
		$pdo->exec("CREATE TABLE IF NOT EXISTS uploaded_awards (
			id INT AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255) NOT NULL,
			description TEXT NULL,
			category VARCHAR(100) NULL,
			requirements TEXT NOT NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		)");
		$stmt = $pdo->query("SELECT id, name, description, category, requirements FROM uploaded_awards ORDER BY id DESC LIMIT 2000");
		$uploadedAwards = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch (Throwable $e) {
		$uploadedAwards = [];
	}
}

// Build pairs for python batch
$pairs = [];
$categories = array_unique(array_merge(array_keys($awardCriteria), array_keys($activities)));
foreach ($categories as $cat) {
	$pairs[] = [
		'set_a' => isset($awardCriteria[$cat]) ? array_values($awardCriteria[$cat]) : [],
		'set_b' => isset($activities[$cat]) ? array_values($activities[$cat]) : [],
		'category' => $cat
	];
}

$python_result = run_python_jaccard(['pairs' => array_map(function($p){ return ['set_a' => $p['set_a'], 'set_b' => $p['set_b']]; }, $pairs)]);

$scores = [];
if ($python_result && isset($python_result['results']) && is_array($python_result['results'])) {
	for ($i = 0; $i < count($pairs); $i++) {
		$cat = $pairs[$i]['category'];
		$sim = floatval($python_result['results'][$i] ?? 0.0);
		$scores[$cat] = round($sim * 100);
	}
} else {
	// Fallback to PHP implementation
	foreach ($pairs as $p) {
		$sim = php_jaccard_similarity($p['set_a'], $p['set_b']);
		$scores[$p['category']] = round($sim * 100);
	}
}

// Compute overall and best match
$overall = 0;
$bestCategory = null;
$bestScore = -1;
foreach ($scores as $cat => $score) {
	$overall += $score;
	if ($score > $bestScore) { $bestScore = $score; $bestCategory = $cat; }
}
$overall = count($scores) > 0 ? round($overall / max(1, count($scores))) : 0;

// If uploaded awards exist, compute top 3 matching criteria per award using Jaccard
$awardMatches = [];
if (!empty($uploadedAwards)) {
	foreach ($uploadedAwards as $award) {
		$req = strtolower($award['requirements'] ?? '');
		$reqTokens = array_values(array_filter(preg_split('/[^a-z0-9]+/i', $req)));
		$reqSet = array_fill_keys($reqTokens, true);
		$catScores = [];
		foreach ($awardCriteria as $cat => $keywords) {
			$kwSet = array_fill_keys(array_map('strval', $keywords), true);
			$inter = 0; foreach ($reqSet as $k => $_) { if (isset($kwSet[$k])) $inter++; }
			$union = count($reqSet) + count($kwSet) - $inter;
			$sim = $union > 0 ? $inter / $union : 1.0;
			$catScores[$cat] = round($sim * 100);
		}
		arsort($catScores);
		$top = array_slice($catScores, 0, 3, true);
		$awardMatches[] = [
			'id' => $award['id'],
			'name' => $award['name'],
			'category' => $award['category'],
			'top_matches' => $top
		];
	}
}

echo json_encode([
	'scores' => $scores,
	'overall' => $overall,
	'best' => $bestCategory,
	'award_matches' => $awardMatches
]);


