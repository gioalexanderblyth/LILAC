<?php
// Create a simple PDF with "Agreement" content for testing
$content = "Agreement

This is a test agreement document.
The content should be extracted and categorized as MOU.

Terms and conditions:
- This is a test document
- Content should be readable
- Should be categorized automatically";

// Create a simple text file first (easier to test)
file_put_contents('uploads/test_agreement.txt', $content);

echo "Created test file: uploads/test_agreement.txt\n";
echo "Content: " . $content . "\n";
echo "File size: " . filesize('uploads/test_agreement.txt') . " bytes\n";
?>
