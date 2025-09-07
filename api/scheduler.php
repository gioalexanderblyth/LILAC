<?php
// Minimal Scheduler API - file storage in data/meetings.json
// Ensures basic actions used by the frontend work without a database

header('Content-Type: application/json');

$DATA_DIR = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
$DATA_FILE = $DATA_DIR . DIRECTORY_SEPARATOR . 'meetings.json';

function ensure_storage() {
	global $DATA_DIR, $DATA_FILE;
	if (!is_dir($DATA_DIR)) {
		@mkdir($DATA_DIR, 0777, true);
	}
	if (!file_exists($DATA_FILE)) {
		@file_put_contents($DATA_FILE, json_encode([ 'next_id' => 1, 'meetings' => [], 'trash' => [] ], JSON_PRETTY_PRINT));
	}
}

function load_db() {
	global $DATA_FILE;
	ensure_storage();
	$raw = @file_get_contents($DATA_FILE);
	if ($raw === false || trim($raw) === '') {
		return [ 'next_id' => 1, 'meetings' => [], 'trash' => [] ];
	}
	$data = json_decode($raw, true);
	if (!is_array($data)) {
		return [ 'next_id' => 1, 'meetings' => [], 'trash' => [] ];
	}
	$data['next_id'] = isset($data['next_id']) ? (int)$data['next_id'] : 1;
	$data['meetings'] = isset($data['meetings']) && is_array($data['meetings']) ? $data['meetings'] : [];
	$data['trash'] = isset($data['trash']) && is_array($data['trash']) ? $data['trash'] : [];
	return $data;
}

function save_db($db) {
	global $DATA_FILE;
	@file_put_contents($DATA_FILE, json_encode($db, JSON_PRETTY_PRINT));
}

function json_ok($payload = []) {
	echo json_encode(array_merge([ 'success' => true ], $payload));
	exit;
}

function json_err($message, $code = 400) {
	http_response_code(200); // keep 200 for frontend expectations
	echo json_encode([ 'success' => false, 'message' => $message, 'code' => $code ]);
	exit;
}

function get_param($key, $default = null) {
	if (isset($_POST[$key])) return $_POST[$key];
	if (isset($_GET[$key])) return $_GET[$key];
	return $default;
}

// Normalize date to YYYY-MM-DD if possible
function normalize_date($d) {
	if (!$d) return '';
	$ts = strtotime($d);
	if ($ts === false) return '';
	return date('Y-m-d', $ts);
}

// Normalize time to HH:MM
function normalize_time($t) {
	if (!$t) return '';
	$ts = strtotime($t);
	if ($ts === false) return '';
	return date('H:i', $ts);
}

$action = get_param('action', get_param('a', 'get_all'));
$db = load_db();

switch ($action) {
	case 'get_all': {
		// Map to expected fields by frontend
		$meetings = array_map(function($m){
			return [
				'id' => $m['id'],
				'title' => $m['title'],
				'meeting_date' => $m['date'],
				'meeting_time' => $m['time'],
				'end_date' => $m['end_date'],
				'end_time' => $m['end_time'],
				'description' => isset($m['description']) ? $m['description'] : '',
				'is_all_day' => isset($m['is_all_day']) ? $m['is_all_day'] : '0',
				'color' => isset($m['color']) ? $m['color'] : 'blue',
				'organizer' => isset($m['organizer']) ? $m['organizer'] : '',
				'venue' => isset($m['venue']) ? $m['venue'] : '',
				'location' => isset($m['venue']) ? $m['venue'] : ''
			];
		}, $db['meetings']);
		json_ok([ 'meetings' => $meetings ]);
	}
	case 'get_upcoming': {
		$limit = (int)get_param('limit', 3);
		if ($limit <= 0) { $limit = 3; }
		$today = date('Y-m-d');
		$filtered = array_values(array_filter($db['meetings'], function($m) use ($today){
			$eventEnd = isset($m['end_date']) && $m['end_date'] ? $m['end_date'] : $m['date'];
			return $eventEnd >= $today;
		}));
		usort($filtered, function($a, $b){
			$ad = ($a['date'] ?? '');
			$at = ($a['time'] ?? '00:00');
			$bd = ($b['date'] ?? '');
			$bt = ($b['time'] ?? '00:00');
			return strcmp($ad . 'T' . $at, $bd . 'T' . $bt);
		});
		$filtered = array_slice($filtered, 0, $limit);
		$mapped = array_map(function($m){
			return [
				'id' => $m['id'],
				'title' => $m['title'],
				'meeting_date' => $m['date'],
				'meeting_time' => $m['time'],
				'end_date' => $m['end_date'],
				'end_time' => $m['end_time'],
				'description' => isset($m['description']) ? $m['description'] : '',
				'is_all_day' => isset($m['is_all_day']) ? $m['is_all_day'] : '0',
				'color' => isset($m['color']) ? $m['color'] : 'blue',
				'organizer' => isset($m['organizer']) ? $m['organizer'] : '',
				'venue' => isset($m['venue']) ? $m['venue'] : '',
				'location' => isset($m['venue']) ? $m['venue'] : ''
			];
		}, $filtered);
		json_ok([ 'meetings' => $mapped ]);
	}
	case 'get_by_date_range': {
		$start = normalize_date(get_param('start_date'));
		$end = normalize_date(get_param('end_date'));
		if (!$start || !$end) json_err('Missing date range');
		$meetings = array_values(array_filter($db['meetings'], function($m) use ($start,$end){
			$eventStart = $m['date'];
			$eventEnd = isset($m['end_date']) && $m['end_date'] ? $m['end_date'] : $m['date'];
			// Overlaps if eventStart <= end AND eventEnd >= start
			return ($eventStart <= $end) && ($eventEnd >= $start);
		}));
		// Keep original keys used by calendar fetch
		$mapped = array_map(function($m){
			return [
				'id' => $m['id'],
				'title' => $m['title'],
				'meeting_date' => $m['date'],
				'meeting_time' => $m['time'],
				'end_date' => $m['end_date'],
				'end_time' => $m['end_time'],
				'description' => isset($m['description']) ? $m['description'] : '',
				'is_all_day' => isset($m['is_all_day']) ? $m['is_all_day'] : '0',
				'color' => isset($m['color']) ? $m['color'] : 'blue',
				'organizer' => isset($m['organizer']) ? $m['organizer'] : '',
				'venue' => isset($m['venue']) ? $m['venue'] : '',
				'location' => isset($m['venue']) ? $m['venue'] : ''
			];
		}, $meetings);
		json_ok([ 'meetings' => $mapped ]);
	}
	case 'add': {
		$title = trim((string)get_param('title'));
		$date = normalize_date(get_param('date'));
		$time = normalize_time(get_param('time'));
		$end_date = normalize_date(get_param('end_date'));
		$end_time = normalize_time(get_param('end_time'));
		$description = trim((string)get_param('description'));
		$is_all_day = get_param('is_all_day', '0') === '1' ? '1' : '0';
		$color = get_param('color', 'blue');
		$organizer = trim((string)get_param('organizer', ''));
		$venue = trim((string)get_param('venue', ''));

		if ($title === '' || $date === '') {
			json_err('Missing required fields (title/date)');
		}
		if ($is_all_day === '1') {
			$time = '00:00';
			$end_time = $end_time ?: '23:59';
			$end_date = $end_date ?: $date;
		}

		$id = $db['next_id']++;
		$db['meetings'][] = [
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'date' => $date,
			'time' => $time,
			'end_date' => $end_date ?: $date,
			'end_time' => $end_time ?: $time,
			'is_all_day' => $is_all_day,
			'color' => $color,
			'organizer' => $organizer,
			'venue' => $venue
		];
		save_db($db);
		json_ok([ 'id' => $id ]);
	}
	case 'update': {
		$id = (int)get_param('id');
		if ($id <= 0) json_err('Invalid id');
		foreach ($db['meetings'] as &$m) {
			if ($m['id'] === $id) {
				$m['title'] = trim((string)get_param('title', $m['title']));
				$m['description'] = trim((string)get_param('description', $m['description']));
				$newDate = normalize_date(get_param('date', $m['date']));
				$newTime = normalize_time(get_param('time', $m['time']));
				$m['date'] = $newDate ?: $m['date'];
				$m['time'] = $newTime ?: $m['time'];
				$m['end_date'] = normalize_date(get_param('end_date', $m['end_date'])) ?: $m['end_date'];
				$m['end_time'] = normalize_time(get_param('end_time', $m['end_time'])) ?: $m['end_time'];
				$m['is_all_day'] = get_param('is_all_day', $m['is_all_day']);
				$m['color'] = get_param('color', $m['color']);
				$m['organizer'] = trim((string)get_param('organizer', isset($m['organizer']) ? $m['organizer'] : ''));
				$m['venue'] = trim((string)get_param('venue', isset($m['venue']) ? $m['venue'] : ''));
				save_db($db);
				json_ok();
			}
		}
		json_err('Meeting not found');
	}
	case 'delete': {
		$id = (int)get_param('id');
		if ($id <= 0) json_err('Invalid id');
		foreach ($db['meetings'] as $i => $m) {
			if ($m['id'] === $id) {
				$removed = $m;
				unset($db['meetings'][$i]);
				$removed['original_id'] = $removed['id'];
				$removed['deleted_at'] = date('c');
				$db['trash'][] = $removed;
				$db['meetings'] = array_values($db['meetings']);
				save_db($db);
				json_ok([ 'message' => 'moved to trash' ]);
			}
		}
		json_err('Meeting not found');
	}
	case 'get_trash': {
		json_ok([ 'meetings' => array_values($db['trash']) ]);
	}
	case 'restore': {
		$trash_id = (int)get_param('trash_id');
		foreach ($db['trash'] as $i => $t) {
			if ($t['id'] === $trash_id || $t['original_id'] === $trash_id) {
				$restored = $t;
				unset($db['trash'][$i]);
				// restore as new id to avoid collisions
				$restored['id'] = $db['next_id']++;
				unset($restored['deleted_at']);
				if (isset($restored['original_id'])) unset($restored['original_id']);
				$db['meetings'][] = $restored;
				save_db($db);
				json_ok();
			}
		}
		json_err('Trash item not found');
	}
	case 'permanently_delete': {
		$trash_id = (int)get_param('trash_id');
		foreach ($db['trash'] as $i => $t) {
			if ($t['id'] === $trash_id || $t['original_id'] === $trash_id) {
				unset($db['trash'][$i]);
				$db['trash'] = array_values($db['trash']);
				save_db($db);
				json_ok();
			}
		}
		json_err('Trash item not found');
	}
	case 'empty_trash': {
		$db['trash'] = [];
		save_db($db);
		json_ok();
	}
	case 'bulk_delete': {
		$ids = json_decode(get_param('meeting_ids', '[]'), true);
		if (!is_array($ids)) $ids = [];
		$moved = 0;
		foreach ($ids as $id) {
			$id = (int)$id;
			foreach ($db['meetings'] as $i => $m) {
				if ($m['id'] === $id) {
					$removed = $m;
					unset($db['meetings'][$i]);
					$removed['original_id'] = $removed['id'];
					$removed['deleted_at'] = date('c');
					$db['trash'][] = $removed;
					$moved++;
				}
			}
		}
		$db['meetings'] = array_values($db['meetings']);
		save_db($db);
		json_ok([ 'moved' => $moved ]);
	}
	case 'bulk_restore': {
		$ids = json_decode(get_param('meeting_ids', '[]'), true);
		if (!is_array($ids)) $ids = [];
		$restoredCount = 0;
		foreach ($ids as $trash_id) {
			foreach ($db['trash'] as $i => $t) {
				if ($t['id'] === (int)$trash_id || $t['original_id'] === (int)$trash_id) {
					$restored = $t;
					unset($db['trash'][$i]);
					$restored['id'] = $db['next_id']++;
					unset($restored['deleted_at']);
					if (isset($restored['original_id'])) unset($restored['original_id']);
					$db['meetings'][] = $restored;
					$restoredCount++;
				}
			}
		}
		$db['trash'] = array_values($db['trash']);
		save_db($db);
		json_ok([ 'restored' => $restoredCount ]);
	}
	default:
		json_err('Unknown action: ' . $action);
} 