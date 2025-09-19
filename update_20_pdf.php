<!DOCTYPE html>
<html>
<head>
    <title>Update 20.pdf</title>
</head>
<body>
    <h2>Update 20.pdf Content</h2>
    
    <?php
    if ($_POST['update']) {
        try {
            require_once 'config/database.php';
            $pdo = getDatabase();
            
            // Update document 45 (assuming this is the 20.pdf)
            $stmt = $pdo->prepare("UPDATE enhanced_documents SET extracted_content = 'Agreement', category = 'MOU' WHERE id = 45");
            $result = $stmt->execute();
            
            if ($result) {
                echo "<p style='color: green;'>✅ Successfully updated document 45 with content 'Agreement' and category 'MOU'</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to update document</p>";
            }
            
            // Show current status
            $checkStmt = $pdo->prepare("SELECT id, original_filename, extracted_content, category FROM enhanced_documents WHERE id = 45");
            $checkStmt->execute();
            $doc = $checkStmt->fetch();
            
            if ($doc) {
                echo "<h3>Current Status:</h3>";
                echo "<p>ID: " . $doc['id'] . "</p>";
                echo "<p>Original Filename: " . $doc['original_filename'] . "</p>";
                echo "<p>Content: '" . $doc['extracted_content'] . "'</p>";
                echo "<p>Category: " . $doc['category'] . "</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
    ?>
    
    <form method="post">
        <button type="submit" name="update" value="1">Update 20.pdf Content</button>
    </form>
    
    <h3>Test the Fix:</h3>
    <p><a href="documents.php" target="_blank">Go to Documents Page</a></p>
    <p><a href="api/documents.php?action=get_all" target="_blank">Check API Response</a></p>
</body>
</html>
