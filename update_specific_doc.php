<!DOCTYPE html>
<html>
<head>
    <title>Update Specific Document</title>
</head>
<body>
    <h2>Update Document</h2>
    
    <?php
    $docId = $_GET['id'] ?? null;
    
    if ($docId && $_POST['update']) {
        try {
            require_once 'config/database.php';
            $pdo = getDatabase();
            
            // Update the specific document
            $stmt = $pdo->prepare("UPDATE enhanced_documents SET extracted_content = 'Agreement', category = 'MOU' WHERE id = ?");
            $result = $stmt->execute([$docId]);
            
            if ($result) {
                echo "<p style='color: green;'>✅ Successfully updated document $docId with content 'Agreement' and category 'MOU'</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to update document $docId</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
    
    if ($docId) {
        try {
            require_once 'config/database.php';
            $pdo = getDatabase();
            
            // Get document details
            $stmt = $pdo->prepare("SELECT * FROM enhanced_documents WHERE id = ?");
            $stmt->execute([$docId]);
            $doc = $stmt->fetch();
            
            if ($doc) {
                echo "<h3>Document Details:</h3>";
                echo "<p><strong>ID:</strong> " . $doc['id'] . "</p>";
                echo "<p><strong>Original Filename:</strong> " . htmlspecialchars($doc['original_filename']) . "</p>";
                echo "<p><strong>Document Name:</strong> " . htmlspecialchars($doc['document_name']) . "</p>";
                echo "<p><strong>Filename:</strong> " . htmlspecialchars($doc['filename']) . "</p>";
                echo "<p><strong>Current Content:</strong> '" . htmlspecialchars($doc['extracted_content']) . "'</p>";
                echo "<p><strong>Current Category:</strong> " . htmlspecialchars($doc['category']) . "</p>";
                
                echo "<form method='post'>";
                echo "<button type='submit' name='update' value='1'>Update This Document with 'Agreement' Content</button>";
                echo "</form>";
                
            } else {
                echo "<p style='color: red;'>Document with ID $docId not found.</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>No document ID provided.</p>";
    }
    ?>
    
    <p><a href="find_real_20_pdf.php">← Back to Find Documents</a></p>
    <p><a href="documents.php">Go to Documents Page</a></p>
</body>
</html>
