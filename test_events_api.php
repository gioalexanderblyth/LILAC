<?php
// Test the events API
echo "Testing Events API...\n";

$url = "http://localhost/LILAC/api/central_events_api.php?action=get_events_by_status";
echo "URL: $url\n";

$response = file_get_contents($url);
echo "Response: " . htmlspecialchars($response) . "\n";

// Try to decode JSON
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "JSON is valid\n";
    print_r($decoded);
} else {
    echo "JSON error: " . json_last_error_msg() . "\n";
}
?>
