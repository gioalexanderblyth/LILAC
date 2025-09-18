<?php
/**
 * Enhanced File Processor with PDF and Office Document Support
 * Uses PHP libraries for content extraction without external dependencies
 */

class EnhancedFileProcessor {
    private $pdo;
    private $uploadsDir;
    
    public function __construct($pdo, $uploadsDir = 'uploads/') {
        $this->pdo = $pdo;
        $this->uploadsDir = $uploadsDir;
    }
    
    /**
     * Extract content from various file types
     */
    public function extractContent($file, $filePath) {
        // Check if file exists before getting mime type
        if (file_exists($filePath)) {
            $fileType = mime_content_type($filePath);
        } else {
            // Fallback to file extension-based type detection
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $fileType = $this->getMimeTypeFromExtension($extension);
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        switch ($fileType) {
            case 'application/pdf':
                return $this->extractFromPDF($filePath);
                
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return $this->extractFromWord($filePath, $extension);
                
            case 'text/plain':
                return $this->extractFromText($filePath);
                
            case 'image/jpeg':
            case 'image/png':
            case 'image/gif':
            case 'image/bmp':
            case 'image/webp':
                return $this->extractFromImage($filePath);
                
            default:
                return $this->extractFromFilename($file['name']);
        }
    }
    
    /**
     * Extract text from PDF files using dedicated PDF extractor
     */
    private function extractFromPDF($filePath) {
        try {
            // Use the dedicated PDF text extractor
            require_once __DIR__ . '/PDFTextExtractor.php';
            return PDFTextExtractor::extractText($filePath);
            
        } catch (Exception $e) {
            error_log("PDF extraction error: " . $e->getMessage());
            return $this->extractFromFilename(basename($filePath));
        }
    }
    
    /**
     * Extract text from Word documents
     */
    private function extractFromWord($filePath, $extension) {
        try {
            if ($extension === 'docx' && class_exists('ZipArchive')) {
                // For DOCX files, extract from XML
                $zip = new ZipArchive();
                if ($zip->open($filePath) === TRUE) {
                    $content = $zip->getFromName('word/document.xml');
                    $zip->close();
                    
                    if ($content) {
                        // Remove XML tags and extract text
                        $text = strip_tags($content);
                        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                        $text = preg_replace('/\s+/', ' ', $text);
                        return trim($text);
                    }
                }
            } elseif ($extension === 'doc') {
                // For older DOC files, try to extract basic text
                // This is more complex and would require additional libraries
                return $this->extractFromFilename(basename($filePath));
            }
        } catch (Exception $e) {
            error_log("Word extraction error: " . $e->getMessage());
        }
        
        return $this->extractFromFilename(basename($filePath));
    }
    
    /**
     * Extract text from plain text files
     */
    private function extractFromText($filePath) {
        $content = file_get_contents($filePath);
        return $content ? trim($content) : '';
    }
    
    /**
     * Extract text from images (placeholder for OCR)
     */
    private function extractFromImage($filePath) {
        // For now, just return filename-based content
        // In production, you could integrate with OCR services
        return $this->extractFromFilename(basename($filePath));
    }
    
    /**
     * Get MIME type from file extension
     */
    private function getMimeTypeFromExtension($extension) {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    /**
     * Extract content from filename
     */
    private function extractFromFilename($filename) {
        // Remove file extension
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Replace underscores and hyphens with spaces
        $name = str_replace(['_', '-'], ' ', $name);
        
        // Add common keywords based on filename patterns
        $keywords = [];
        
        if (stripos($name, 'mou') !== false) {
            $keywords[] = 'memorandum of understanding';
        }
        if (stripos($name, 'moa') !== false) {
            $keywords[] = 'memorandum of agreement';
        }
        if (stripos($name, 'agreement') !== false) {
            $keywords[] = 'agreement';
        }
        if (stripos($name, 'partnership') !== false) {
            $keywords[] = 'partnership';
        }
        if (stripos($name, 'international') !== false) {
            $keywords[] = 'international';
        }
        if (stripos($name, 'education') !== false) {
            $keywords[] = 'education';
        }
        if (stripos($name, 'research') !== false) {
            $keywords[] = 'research';
        }
        if (stripos($name, 'collaboration') !== false) {
            $keywords[] = 'collaboration';
        }
        
        $content = $name;
        if (!empty($keywords)) {
            $content .= ' ' . implode(' ', $keywords);
        }
        
        return $content;
    }
    
    /**
     * Process uploaded file with enhanced content extraction
     */
    public function processUpload($file, $additionalData = []) {
        try {
            // Validate file
            $this->validateFile($file);
            
            // Save file
            $filePath = $this->saveFile($file);
            
            // Extract content using enhanced methods
            $extractedContent = $this->extractContent($file, $filePath);
            
            // Store in database
            $documentId = $this->storeDocument($file, $filePath, $extractedContent, $additionalData);
            
            return [
                'success' => true,
                'document_id' => $documentId,
                'file_path' => $filePath,
                'extracted_content' => $extractedContent,
                'content_length' => strlen($extractedContent)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('No file uploaded');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $file['error']);
        }
        
        if ($file['size'] <= 0) {
            throw new Exception('File size is zero');
        }
        
        if ($file['size'] > 50 * 1024 * 1024) { // 50MB limit
            throw new Exception('File size exceeds 50MB limit');
        }
        
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/webp'
        ];
        
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('File type not supported: ' . $fileType);
        }
    }
    
    /**
     * Save file to disk
     */
    private function saveFile($file) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('doc_', true) . '.' . $extension;
        $filePath = $this->uploadsDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        return $filePath;
    }
    
    /**
     * Store document in database
     */
    private function storeDocument($file, $filePath, $extractedContent, $additionalData) {
        $stmt = $this->pdo->prepare("
            INSERT INTO enhanced_documents 
            (document_name, filename, original_filename, file_path, file_size, file_type, category, description, extracted_content) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $additionalData['document_name'] ?? $file['name'],
            basename($filePath),
            $file['name'],
            $filePath,
            $file['size'],
            mime_content_type($filePath),
            $additionalData['category'] ?? 'General',
            $additionalData['description'] ?? '',
            $extractedContent
        ]);
        
        return $this->pdo->lastInsertId();
    }
}
?>
