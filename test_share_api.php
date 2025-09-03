<?php
// Test the share API functionality
echo "<h2>Testing Share API</h2>";

// Test 1: Check if the Document class getById method exists
require_once 'classes/Document.php';
$document = new Document();

if (method_exists($document, 'getById')) {
    echo "<p style='color: green;'>✓ Document::getById method exists</p>";
} else {
    echo "<p style='color: red;'>✗ Document::getById method does not exist</p>";
}

// Test 2: Check if we can get a document by ID
$testDoc = $document->getById(1);
if ($testDoc) {
    echo "<p style='color: green;'>✓ Can fetch document by ID. Found: " . htmlspecialchars($testDoc['document_name']) . "</p>";
} else {
    echo "<p style='color: orange;'>⚠ No document found with ID 1 (this might be normal if no documents exist)</p>";
}

// Test 3: Check mail function
if (function_exists('mail')) {
    echo "<p style='color: green;'>✓ PHP mail() function is available</p>";
} else {
    echo "<p style='color: red;'>✗ PHP mail() function is not available</p>";
}

// Test 4: Check if we can make a test API call
echo "<h3>Testing API Call</h3>";
echo "<form method='post' action='api/documents.php?action=send_email_share'>";
echo "<input type='hidden' name='email' value='test@example.com'>";
echo "<input type='hidden' name='documents' value='[1]'>";
echo "<input type='hidden' name='message' value='Test message'>";
echo "<button type='submit'>Test Share API</button>";
echo "</form>";

// Test 5: Show recent error logs
echo "<h3>Recent Error Logs (last 10 lines)</h3>";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -10);
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    foreach ($recentLines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>No error log found or error logging not configured</p>";
}
?> 