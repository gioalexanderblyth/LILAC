<!DOCTYPE html>
<html>
<head>
    <title>Find 20.pdf Document</title>
</head>
<body>
    <h2>Find the Real 20.pdf Document</h2>
    
    <?php
    try {
        require_once 'config/database.php';
        $pdo = getDatabase();
        
        // Find ALL documents that might be "20.pdf"
        $stmt = $pdo->prepare("SELECT id, original_filename, document_name, filename, extracted_content, category FROM enhanced_documents WHERE original_filename LIKE '%20%' OR document_name LIKE '%20%' OR filename LIKE '%20%'");
        $stmt->execute();
        $docs = $stmt->fetchAll();
        
        echo "<h3>Found " . count($docs) . " documents with '20' in the name:</h3>";
        
        if (count($docs) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Original Filename</th><th>Document Name</th><th>Filename</th><th>Content</th><th>Category</th><th>Action</th></tr>";
            
            foreach ($docs as $doc) {
                echo "<tr>";
                echo "<td>" . $doc['id'] . "</td>";
                echo "<td>" . htmlspecialchars($doc['original_filename']) . "</td>";
                echo "<td>" . htmlspecialchars($doc['document_name']) . "</td>";
                echo "<td>" . htmlspecialchars($doc['filename']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($doc['extracted_content'], 0, 50)) . "...</td>";
                echo "<td>" . htmlspecialchars($doc['category']) . "</td>";
                echo "<td><a href='update_specific_doc.php?id=" . $doc['id'] . "'>Update This One</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No documents found with '20' in the name.</p>";
            
            // Show all documents
            $allStmt = $pdo->query("SELECT id, original_filename, document_name, filename, extracted_content, category FROM enhanced_documents ORDER BY id DESC LIMIT 20");
            $allDocs = $allStmt->fetchAll();
            
            echo "<h3>Last 20 documents in database:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Original Filename</th><th>Document Name</th><th>Filename</th><th>Content</th><th>Category</th></tr>";
            
            foreach ($allDocs as $doc) {
                echo "<tr>";
                echo "<td>" . $doc['id'] . "</td>";
                echo "<td>" . htmlspecialchars($doc['original_filename']) . "</td>";
                echo "<td>" . htmlspecialchars($doc['document_name']) . "</td>";
                echo "<td>" . htmlspecialchars($doc['filename']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($doc['extracted_content'], 0, 30)) . "...</td>";
                echo "<td>" . htmlspecialchars($doc['category']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h3>Upload a New 20.pdf File:</h3>
    <p><a href="documents.php">Go to Documents Page to Upload</a></p>
</body>
</html>
