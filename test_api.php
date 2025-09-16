<?php
// Test if PHP is working and APIs are accessible
echo "PHP is working!<br>";

// Test awards API
echo "<h3>Testing Awards API:</h3>";
$url = "http://localhost/LILAC/api/awards.php?action=get_stats";
$response = file_get_contents($url);
echo "Response: " . htmlspecialchars($response) . "<br>";

// Test MOU API
echo "<h3>Testing MOU API:</h3>";
$url = "http://localhost/LILAC/api/mous.php?action=list";
$response = file_get_contents($url);
echo "Response: " . htmlspecialchars($response) . "<br>";
?>
