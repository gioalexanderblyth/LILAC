<?php
echo "=== SIMPLE DEBUG ===\n";

// Test 1: Basic file check
$file = 'uploads/doc_68cc1e1ae77cb.pdf';
if (file_exists($file)) {
    echo "PDF exists: YES\n";
    echo "Size: " . filesize($file) . " bytes\n";
} else {
    echo "PDF exists: NO\n";
}

// Test 2: Database check
try {
    require_once 'config/database.php';
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT extracted_content, category FROM enhanced_documents WHERE id = 45");
    $stmt->execute();
    $doc = $stmt->fetch();
    
    if ($doc) {
        echo "DB Content: '" . $doc['extracted_content'] . "'\n";
        echo "DB Category: " . $doc['category'] . "\n";
    } else {
        echo "Document 45 not found\n";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

echo "=== END ===\n";
?>
