<?php
// Simple test to check the delete API endpoint
echo "<h2>Testing Delete API Endpoint</h2>";

// Test the API endpoint directly
$base = dirname(__DIR__) ? rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR) : __DIR__;
$url = 'http://localhost/LILAC/api/documents.php';

// First, let's get a list of documents to see what we can delete
echo "<h3>1. Getting list of documents:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url . '?action=get_all&limit=5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";

// Parse the response
$data = json_decode($response, true);
if ($data && isset($data['documents']) && count($data['documents']) > 0) {
    $firstDoc = $data['documents'][0];
    echo "<h3>2. Testing delete with document ID: " . $firstDoc['id'] . "</h3>";
    
    // Test the delete endpoint
    $postData = [
        'action' => 'delete',
        'id' => $firstDoc['id']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $deleteResponse = curl_exec($ch);
    $deleteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Delete HTTP Code: " . $deleteHttpCode . "<br>";
    echo "Delete Response: <pre>" . htmlspecialchars($deleteResponse) . "</pre><br>";
    
    $deleteData = json_decode($deleteResponse, true);
    if ($deleteData) {
        echo "Parsed Response: <pre>" . print_r($deleteData, true) . "</pre><br>";
    }
} else {
    echo "<p>No documents found to test with.</p>";
}

echo "<h3>3. API Endpoint Status:</h3>";
echo "URL: " . $url . "<br>";
echo "File exists: " . (file_exists($base . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'documents.php') ? 'Yes' : 'No') . "<br>";
echo "File readable: " . (is_readable($base . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'documents.php') ? 'Yes' : 'No') . "<br>";
?> 