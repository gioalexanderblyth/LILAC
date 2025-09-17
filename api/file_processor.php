<?php
/**
 * Comprehensive File Processing System
 * Handles PDF, Word, Text, and Image file content extraction with OCR
 */

class FileProcessor {
    private $pdo;
    private $uploadsDir;
    
    public function __construct($pdo, $uploadsDir = '../uploads/') {
        $this->pdo = $pdo;
        $this->uploadsDir = rtrim($uploadsDir, '/') . '/';
        
        // Ensure uploads directory exists
        if (!is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0755, true);
        }
        
        // Create file_processing_log table if it doesn't exist
        $this->createFileProcessingLogTable();
    }
    
    /**
     * Create file_processing_log table if it doesn't exist
     */
    private function createFileProcessingLogTable() {
        $sql = "CREATE TABLE IF NOT EXISTS file_processing_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_id INT NULL,
            file_type VARCHAR(50) NOT NULL,
            processing_status ENUM('processing', 'completed', 'failed') DEFAULT 'processing',
            extracted_content_length INT NULL,
            processing_time_ms INT NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            INDEX idx_status (processing_status),
            INDEX idx_file_type (file_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Process uploaded file and extract content
     */
    public function processFile($file, $additionalData = []) {
        $startTime = microtime(true);
        $logId = $this->logProcessingStart($file, 'document');
        
        try {
            // Validate file
            $this->validateFile($file);
            
            // Save file to disk
            $filePath = $this->saveFile($file);
            
            // Extract content based on file type
            $extractedContent = $this->extractContent($file, $filePath);
            
            // Store in database
            $documentId = $this->storeDocument($file, $filePath, $extractedContent, $additionalData);
            
            // Log successful processing
            $processingTime = (microtime(true) - $startTime) * 1000;
            $this->logProcessingComplete($logId, $documentId, strlen($extractedContent), $processingTime);
            
            return [
                'success' => true,
                'document_id' => $documentId,
                'file_path' => $filePath,
                'extracted_content' => $extractedContent,
                'content_length' => strlen($extractedContent)
            ];
            
        } catch (Exception $e) {
            $this->logProcessingError($logId, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Process uploaded event with file
     */
    public function processEvent($eventData, $file = null) {
        $startTime = microtime(true);
        $logId = $this->logProcessingStart($file, 'event');
        
        try {
            $filePath = null;
            $extractedContent = '';
            
            // Process file if provided
            if ($file) {
                $this->validateFile($file);
                $filePath = $this->saveFile($file);
                $extractedContent = $this->extractContent($file, $filePath);
            }
            
            // Combine event data with extracted content
            $fullContent = $this->combineEventContent($eventData, $extractedContent);
            
            // Store in database
            $eventId = $this->storeEvent($eventData, $filePath, $fullContent);
            
            // Log successful processing
            $processingTime = (microtime(true) - $startTime) * 1000;
            $this->logProcessingComplete($logId, $eventId, strlen($fullContent), $processingTime);
            
            return [
                'success' => true,
                'event_id' => $eventId,
                'file_path' => $filePath,
                'extracted_content' => $fullContent,
                'content_length' => strlen($fullContent)
            ];
            
        } catch (Exception $e) {
            $this->logProcessingError($logId, $e->getMessage());
            throw $e;
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
        $filename = uniqid('file_', true) . '.' . $extension;
        $filePath = $this->uploadsDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        return $filePath;
    }
    
    /**
     * Extract content from file based on type
     */
    private function extractContent($file, $filePath) {
        $fileType = mime_content_type($filePath);
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
     * Extract text from PDF files
     */
    private function extractFromPDF($filePath) {
        // For PDF extraction, we'll use a simple approach
        // In production, you might want to use a library like pdftotext or PDF.js
        try {
            // Try to use pdftotext if available
            $command = "pdftotext -layout " . escapeshellarg($filePath) . " -";
            $output = shell_exec($command);
            
            if ($output && trim($output)) {
                return trim($output);
            }
        } catch (Exception $e) {
            // Fallback to filename extraction
        }
        
        // Fallback: extract from filename
        return $this->extractFromFilename(basename($filePath));
    }
    
    /**
     * Extract text from Word documents
     */
    private function extractFromWord($filePath, $extension) {
        try {
            if ($extension === 'docx') {
                // For DOCX files, we can use a simple ZIP-based approach
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
            }
        } catch (Exception $e) {
            // Fallback to filename extraction
        }
        
        // Fallback: extract from filename
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
     * Extract text from images using OCR
     */
    private function extractFromImage($filePath) {
        try {
            // Try to use Tesseract OCR if available
            $command = "tesseract " . escapeshellarg($filePath) . " -";
            $output = shell_exec($command);
            
            if ($output && trim($output)) {
                return trim($output);
            }
        } catch (Exception $e) {
            // OCR failed, fallback to filename
        }
        
        // Fallback: extract from filename
        return $this->extractFromFilename(basename($filePath));
    }
    
    /**
     * Extract meaningful text from filename
     */
    private function extractFromFilename($filename) {
        // Remove file extension and common separators
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/[-_]/', ' ', $name);
        return strtolower(trim($name));
    }
    
    /**
     * Combine event data with extracted content
     */
    private function combineEventContent($eventData, $extractedContent) {
        $content = '';
        
        if (!empty($eventData['title'])) {
            $content .= $eventData['title'] . ' ';
        }
        
        if (!empty($eventData['description'])) {
            $content .= $eventData['description'] . ' ';
        }
        
        if (!empty($eventData['location'])) {
            $content .= $eventData['location'] . ' ';
        }
        
        if (!empty($extractedContent)) {
            $content .= $extractedContent . ' ';
        }
        
        return trim($content);
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
            basename($filePath), // Use the generated filename
            $file['name'], // Store original filename
            $filePath,
            $file['size'],
            mime_content_type($filePath),
            $additionalData['category'] ?? 'Awards', // Add category field
            $additionalData['description'] ?? '',
            $extractedContent
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Store event in database
     */
    private function storeEvent($eventData, $filePath, $extractedContent) {
        $stmt = $this->pdo->prepare("
            INSERT INTO enhanced_events 
            (title, description, event_date, event_time, location, file_path, file_type, extracted_content) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $eventData['title'],
            $eventData['description'] ?? '',
            $eventData['event_date'],
            $eventData['event_time'] ?? null,
            $eventData['location'] ?? '',
            $filePath,
            $filePath ? mime_content_type($filePath) : null,
            $extractedContent
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Log processing start
     */
    private function logProcessingStart($file, $fileType) {
        $stmt = $this->pdo->prepare("
            INSERT INTO file_processing_log (file_id, file_type, processing_status) 
            VALUES (?, ?, 'processing')
        ");
        
        $stmt->execute([null, $fileType]);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Log processing completion
     */
    private function logProcessingComplete($logId, $fileId, $contentLength, $processingTime) {
        $stmt = $this->pdo->prepare("
            UPDATE file_processing_log 
            SET file_id = ?, processing_status = 'completed', 
                extracted_content_length = ?, processing_time_ms = ?, completed_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$fileId, $contentLength, $processingTime, $logId]);
    }
    
    /**
     * Log processing error
     */
    private function logProcessingError($logId, $errorMessage) {
        $stmt = $this->pdo->prepare("
            UPDATE file_processing_log 
            SET processing_status = 'failed', error_message = ?, completed_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$errorMessage, $logId]);
    }
    
    /**
     * Get processing statistics
     */
    public function getProcessingStats() {
        $stmt = $this->pdo->query("
            SELECT 
                file_type,
                processing_status,
                COUNT(*) as count,
                AVG(processing_time_ms) as avg_processing_time,
                AVG(extracted_content_length) as avg_content_length
            FROM file_processing_log 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY file_type, processing_status
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
