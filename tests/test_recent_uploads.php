<?php
// Simple test to check recent uploads API
echo "<h2>Testing Recent Uploads API</h2>";

// Test the API endpoint directly
$base = dirname(__DIR__) ? rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR) : __DIR__;
$url = 'http://localhost/LILAC/api/documents.php';

echo "<h3>1. Testing recent uploads API:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url . '?action=get_all&limit=5&sort_by=upload_date&sort_order=DESC');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";

// Parse the response
$data = json_decode($response, true);
if ($data && isset($data['documents'])) {
    echo "<h3>2. Documents found:</h3>";
    echo "Total documents: " . count($data['documents']) . "<br>";
    foreach ($data['documents'] as $index => $doc) {
        echo "Document " . ($index + 1) . ": " . ($doc['document_name'] ?? $doc['title'] ?? 'Untitled') . " (Uploaded: " . ($doc['upload_date'] ?? 'Unknown') . ")<br>";
    }
} else {
    echo "<p>No documents found or invalid response.</p>";
}

echo "<h3>3. API Endpoint Status:</h3>";
echo "URL: " . $url . "<br>";
echo "File exists: " . (file_exists($base . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'documents.php') ? 'Yes' : 'No') . "<br>";
echo "File readable: " . (is_readable($base . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'documents.php') ? 'Yes' : 'No') . "<br>";
?> 