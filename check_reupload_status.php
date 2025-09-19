<?php
/**
 * Check the status of the re-uploaded file and MOU synchronization
 */

require_once 'config/database.php';

echo "<h2>Checking Re-upload Status</h2>\n";

try {
    $pdo = getDatabase();
    
    // Check for recent uploads
    echo "<h3>1. Recent Database Entries:</h3>\n";
    $stmt = $pdo->query("SELECT id, original_filename, document_name, filename, category, extracted_content, is_readable, created_at FROM enhanced_documents ORDER BY id DESC LIMIT 10");
    $docs = $stmt->fetchAll();
    
    if ($docs) {
        foreach ($docs as $doc) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; background: #f9f9f9;'>\n";
            echo "<p><strong>ID:</strong> " . $doc['id'] . " | <strong>Created:</strong> " . $doc['created_at'] . "</p>\n";
            echo "<p><strong>Original Filename:</strong> " . htmlspecialchars($doc['original_filename']) . "</p>\n";
            echo "<p><strong>Document Name:</strong> " . htmlspecialchars($doc['document_name']) . "</p>\n";
            echo "<p><strong>Category:</strong> " . htmlspecialchars($doc['category']) . "</p>\n";
            echo "<p><strong>Is Readable:</strong> " . ($doc['is_readable'] ? 'Yes' : 'No') . "</p>\n";
            echo "<p><strong>Content Length:</strong> " . strlen($doc['extracted_content']) . " characters</p>\n";
            echo "<p><strong>Content Preview:</strong> '" . htmlspecialchars(substr($doc['extracted_content'], 0, 150)) . "'</p>\n";
            
            // Check if this looks like the MOA content
            if (strpos($doc['extracted_content'], 'Memorandum of Agreement') !== false || 
                strpos($doc['extracted_content'], 'international collaboration') !== false) {
                echo "<p style='color: green; font-weight: bold;'>✅ This appears to be the MOA content!</p>\n";
            }
            echo "</div>\n";
        }
    } else {
        echo "<p>No documents found in database.</p>\n";
    }
    
    // Check MOU table specifically
    echo "<h3>2. MOU Table Status:</h3>\n";
    $stmt = $pdo->query("SELECT id, partner_name, file_name, agreement_type, status, created_at FROM mous ORDER BY id DESC LIMIT 10");
    $mous = $stmt->fetchAll();
    
    if ($mous) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Partner Name</th><th>File Name</th><th>Agreement Type</th><th>Status</th><th>Created</th></tr>\n";
        foreach ($mous as $mou) {
            echo "<tr>";
            echo "<td>" . $mou['id'] . "</td>";
            echo "<td>" . htmlspecialchars($mou['partner_name']) . "</td>";
            echo "<td>" . htmlspecialchars($mou['file_name']) . "</td>";
            echo "<td>" . htmlspecialchars($mou['agreement_type']) . "</td>";
            echo "<td>" . htmlspecialchars($mou['status']) . "</td>";
            echo "<td>" . $mou['created_at'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>No entries found in MOU table.</p>\n";
    }
    
    // Check for the specific file
    echo "<h3>3. Searching for File 11 or MOA Content:</h3>\n";
    $searchTerms = [
        "original_filename LIKE '%11%'",
        "document_name LIKE '%11%'", 
        "extracted_content LIKE '%Memorandum of Agreement%'",
        "extracted_content LIKE '%international collaboration%'",
        "extracted_content LIKE '%leadership in research%'"
    ];
    
    foreach ($searchTerms as $search) {
        echo "<h4>Search: $search</h4>\n";
        $stmt = $pdo->prepare("SELECT id, original_filename, document_name, category, extracted_content, is_readable FROM enhanced_documents WHERE $search");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        if ($results) {
            foreach ($results as $result) {
                echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid #4CAF50; margin: 5px 0;'>\n";
                echo "<p><strong>ID:</strong> " . $result['id'] . "</p>\n";
                echo "<p><strong>Original:</strong> " . htmlspecialchars($result['original_filename']) . "</p>\n";
                echo "<p><strong>Name:</strong> " . htmlspecialchars($result['document_name']) . "</p>\n";
                echo "<p><strong>Category:</strong> " . htmlspecialchars($result['category']) . "</p>\n";
                echo "<p><strong>Readable:</strong> " . ($result['is_readable'] ? 'Yes' : 'No') . "</p>\n";
                echo "<p><strong>Content:</strong> '" . htmlspecialchars(substr($result['extracted_content'], 0, 200)) . "'</p>\n";
                echo "</div>\n";
            }
        } else {
            echo "<p>No results found.</p>\n";
        }
    }
    
    // Check file processing logs
    echo "<h3>4. Recent Processing Logs:</h3>\n";
    if (file_exists('logs/file_processing.log')) {
        $logContent = file_get_contents('logs/file_processing.log');
        $logLines = array_slice(explode("\n", $logContent), -10); // Last 10 lines
        
        echo "<div style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; font-family: monospace; font-size: 12px;'>\n";
        foreach ($logLines as $line) {
            if (!empty(trim($line))) {
                echo htmlspecialchars($line) . "<br>\n";
            }
        }
        echo "</div>\n";
    } else {
        echo "<p>No processing log found.</p>\n";
    }
    
    // Check uploaded files
    echo "<h3>5. Current Uploaded Files:</h3>\n";
    $uploadDir = 'uploads/';
    $files = glob($uploadDir . '*');
    
    if ($files) {
        echo "<ul>\n";
        foreach ($files as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo "<li><strong>$filename</strong> - $size bytes - Modified: $modified</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>No files found in uploads directory.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}

echo "<h3>Actions:</h3>\n";
echo "<p><a href='test_fixed_extraction.php' target='_blank'>Test Fixed Extraction</a></p>\n";
echo "<p><a href='mou-moa.php' target='_blank'>View MOU Page</a></p>\n";
echo "<p><a href='documents.php' target='_blank'>View Documents Page</a></p>\n";
?>
