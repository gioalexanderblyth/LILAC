<?php
// Simple upload test to debug the issue
header("Content-Type: text/html");

echo "<h1>Upload Test</h1>";

// Check PHP upload settings
echo "<h2>PHP Upload Settings:</h2>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";

// Check if uploads directory exists and is writable
echo "<h2>Directory Check:</h2>";
$uploadsDir = 'uploads/';
if (file_exists($uploadsDir)) {
    echo "Uploads directory exists<br>";
    if (is_writable($uploadsDir)) {
        echo "Uploads directory is writable<br>";
    } else {
        echo "Uploads directory is NOT writable<br>";
    }
} else {
    echo "Uploads directory does NOT exist<br>";
}

// Check for POST data
echo "<h2>POST Data:</h2>";
if ($_POST) {
    echo "POST data received:<br>";
    print_r($_POST);
} else {
    echo "No POST data received<br>";
}

// Check for uploaded files
echo "<h2>Uploaded Files:</h2>";
if ($_FILES) {
    echo "Files received:<br>";
    print_r($_FILES);
} else {
    echo "No files received<br>";
}

// Test form
echo "<h2>Test Upload Form:</h2>";
echo '<form method="POST" enctype="multipart/form-data">';
echo '<input type="file" name="testfile" required><br>';
echo '<input type="submit" value="Test Upload">';
echo '</form>';
?> 