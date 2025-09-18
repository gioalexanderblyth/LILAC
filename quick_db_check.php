<?php
/**
 * Quick database check for the specific file
 */

require_once 'config/database.php';

echo "<h2>Quick Database Check for doc_68cc3809e4014.txt</h2>\n";

try {
    $pdo = getDatabase();
    
    // Search for the file in different ways
    $searches = [
        "original_filename LIKE '%doc_68cc3809e4014%'",
        "filename LIKE '%doc_68cc3809e4014%'", 
        "document_name LIKE '%doc_68cc3809e4014%'",
        "original_filename LIKE '%68cc3809e4014%'",
        "filename LIKE '%68cc3809e4014%'"
    ];
    
    foreach ($searches as $search) {
        echo "<h3>Search: $search</h3>\n";
        $stmt = $pdo->prepare("SELECT id, original_filename, document_name, filename, category, extracted_content, is_readable FROM enhanced_documents WHERE $search");
        $stmt->execute();
        $docs = $stmt->fetchAll();
        
        if ($docs) {
            foreach ($docs as $doc) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f9f9f9;'>\n";
                echo "<p><strong>ID:</strong> " . $doc['id'] . "</p>\n";
                echo "<p><strong>Original Filename:</strong> " . htmlspecialchars($doc['original_filename']) . "</p>\n";
                echo "<p><strong>Document Name:</strong> " . htmlspecialchars($doc['document_name']) . "</p>\n";
                echo "<p><strong>Filename:</strong> " . htmlspecialchars($doc['filename']) . "</p>\n";
                echo "<p><strong>Category:</strong> " . htmlspecialchars($doc['category']) . "</p>\n";
                echo "<p><strong>Is Readable:</strong> " . ($doc['is_readable'] ? 'Yes' : 'No') . "</p>\n";
                echo "<p><strong>Content Length:</strong> " . strlen($doc['extracted_content']) . " characters</p>\n";
                echo "<p><strong>Content:</strong> '" . htmlspecialchars(substr($doc['extracted_content'], 0, 200)) . "'</p>\n";
                echo "</div>\n";
            }
        } else {
            echo "<p>No results found</p>\n";
        }
    }
    
    // Also check recent entries
    echo "<h3>Recent Documents (Last 10):</h3>\n";
    $stmt = $pdo->query("SELECT id, original_filename, document_name, filename, category, extracted_content, is_readable FROM enhanced_documents ORDER BY id DESC LIMIT 10");
    $docs = $stmt->fetchAll();
    
    foreach ($docs as $doc) {
        echo "<div style='border: 1px solid #ddd; padding: 5px; margin: 5px 0;'>\n";
        echo "<p><strong>ID:</strong> " . $doc['id'] . " | <strong>Original:</strong> " . htmlspecialchars($doc['original_filename']) . " | <strong>Name:</strong> " . htmlspecialchars($doc['document_name']) . "</p>\n";
        echo "<p><strong>Category:</strong> " . htmlspecialchars($doc['category']) . " | <strong>Readable:</strong> " . ($doc['is_readable'] ? 'Yes' : 'No') . " | <strong>Content:</strong> '" . htmlspecialchars(substr($doc['extracted_content'], 0, 100)) . "'</p>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
