<!DOCTYPE html>
<html>
<head>
    <title>Fix Content Extraction</title>
</head>
<body>
    <h2>Fix Content Extraction Issue</h2>
    
    <?php
    if ($_POST['fix_extraction']) {
        try {
            require_once 'config/database.php';
            $pdo = getDatabase();
            
            // Find the 11.txt document
            $stmt = $pdo->prepare("SELECT * FROM enhanced_documents WHERE original_filename = '11.txt' OR document_name = '11' ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $doc = $stmt->fetch();
            
            if ($doc) {
                echo "<h3>Found Document:</h3>";
                echo "<p>ID: " . $doc['id'] . "</p>";
                echo "<p>File: " . $doc['file_path'] . "</p>";
                echo "<p>Current Content: '" . htmlspecialchars($doc['extracted_content']) . "'</p>";
                
                // Check if file exists
                if (file_exists($doc['file_path'])) {
                    echo "<p>✅ File exists</p>";
                    
                    // Try to read the file directly
                    $fileContent = file_get_contents($doc['file_path']);
                    echo "<p>Direct file read: '" . htmlspecialchars($fileContent) . "'</p>";
                    
                    // If it's a PDF, try to extract text
                    if (pathinfo($doc['file_path'], PATHINFO_EXTENSION) === 'pdf') {
                        echo "<p>This is a PDF file, trying to extract text...</p>";
                        
                        try {
                            require_once 'vendor/autoload.php';
                            $parser = new \Smalot\PdfParser\Parser();
                            $pdf = $parser->parseFile($doc['file_path']);
                            $pdfText = $pdf->getText();
                            echo "<p>PDF extracted text: '" . htmlspecialchars($pdfText) . "'</p>";
                            
                            // Update the database with the extracted content
                            if (!empty($pdfText)) {
                                $updateStmt = $pdo->prepare("UPDATE enhanced_documents SET extracted_content = ?, category = 'MOUs & MOAs' WHERE id = ?");
                                $updateStmt->execute([$pdfText, $doc['id']]);
                                echo "<p style='color: green;'>✅ Updated database with extracted content</p>";
                                
                                // Try MOU sync
                                require_once 'classes/MouSyncManager.php';
                                $mouSync = new MouSyncManager($pdo);
                                $syncResult = $mouSync->syncUpload(
                                    $doc['id'],
                                    $doc['document_name'],
                                    $doc['filename'],
                                    $doc['file_path'],
                                    $doc['file_size'],
                                    $doc['file_type'],
                                    'MOUs & MOAs',
                                    $doc['description'] ?? '',
                                    $pdfText
                                );
                                
                                echo "<p>MOU Sync Result: " . json_encode($syncResult) . "</p>";
                            }
                            
                        } catch (Exception $e) {
                            echo "<p style='color: red;'>PDF extraction error: " . $e->getMessage() . "</p>";
                        }
                    }
                    
                    // If it's a text file, just read it
                    if (pathinfo($doc['file_path'], PATHINFO_EXTENSION) === 'txt') {
                        echo "<p>This is a text file</p>";
                        if (!empty($fileContent)) {
                            $updateStmt = $pdo->prepare("UPDATE enhanced_documents SET extracted_content = ?, category = 'MOUs & MOAs' WHERE id = ?");
                            $updateStmt->execute([$fileContent, $doc['id']]);
                            echo "<p style='color: green;'>✅ Updated database with text content</p>";
                        }
                    }
                    
                } else {
                    echo "<p style='color: red;'>❌ File not found: " . $doc['file_path'] . "</p>";
                }
                
            } else {
                echo "<p style='color: red;'>❌ No 11.txt document found</p>";
                
                // Show all documents
                $allStmt = $pdo->query("SELECT id, original_filename, document_name, file_path, extracted_content FROM enhanced_documents ORDER BY id DESC LIMIT 10");
                $allDocs = $allStmt->fetchAll();
                
                echo "<h3>Recent Documents:</h3>";
                foreach ($allDocs as $d) {
                    echo "<p>ID: " . $d['id'] . " | Original: " . htmlspecialchars($d['original_filename']) . " | Name: " . htmlspecialchars($d['document_name']) . " | Content: '" . htmlspecialchars(substr($d['extracted_content'], 0, 30)) . "'</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
    
    if ($_POST['create_test_file']) {
        // Create a simple test file with Agreement content
        $content = "Agreement between parties for collaboration and partnership.";
        $filename = 'uploads/test_agreement.txt';
        file_put_contents($filename, $content);
        echo "<p style='color: green;'>✅ Created test file: $filename</p>";
        echo "<p>Content: '$content'</p>";
    }
    ?>
    
    <form method="post">
        <button type="submit" name="fix_extraction" value="1">Fix Content Extraction for 11.txt</button>
    </form>
    
    <form method="post">
        <button type="submit" name="create_test_file" value="1">Create Test Agreement File</button>
    </form>
    
    <h3>Check Results:</h3>
    <p><a href="documents.php" target="_blank">View Documents Page</a></p>
    <p><a href="mou-moa.php" target="_blank">View MOU Page</a></p>
    <p><a href="test_content_web.php" target="_blank">Test Content Extraction</a></p>
</body>
</html>
