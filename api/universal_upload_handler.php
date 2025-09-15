<?php
/**
 * Universal Upload Handler
 * Central repository for all uploaded files with intelligent categorization
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/file_processor.php';

class UniversalUploadHandler {
    private $pdo;
    private $fileProcessor;
    private $allowedTypes = ['pdf', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB
    private $categories = ['events', 'mou', 'awards', 'templates'];
    
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->fileProcessor = new FileProcessor($this->pdo, 'uploads/');
        $this->createTable();
    }
    
    /**
     * Create universal files table if it doesn't exist
     */
    private function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS universal_files (
            id VARCHAR(50) PRIMARY KEY,
            original_filename VARCHAR(255) NOT NULL,
            stored_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            uploaded_by VARCHAR(100) DEFAULT 'system',
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            category VARCHAR(50) NOT NULL,
            linked_pages JSON,
            file_size INT,
            mime_type VARCHAR(100),
            extracted_text LONGTEXT,
            event_date DATE NULL,
            status ENUM('active', 'archived', 'deleted') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Handle file upload with categorization
     */
    public function handleUpload($file, $uploadedBy = 'system', $sourcePage = 'docs') {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error']
                ];
            }
            
            // Generate unique ID and filename
            $fileId = uniqid('file_', true);
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $storedFilename = $this->generateStoredFilename($fileId, $extension);
            
            // Extract text content for categorization
            $extractedText = $this->fileProcessor->extractText($file);
            
            // Determine category based on content
            $category = $this->categorizeFile($extractedText, $file['name']);
            
            // Create category directory
            $categoryDir = "uploads/{$category}/";
            if (!is_dir($categoryDir)) {
                mkdir($categoryDir, 0755, true);
            }
            
            // Move file to category directory
            $filePath = $categoryDir . $storedFilename;
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return [
                    'success' => false,
                    'error' => 'Failed to save uploaded file'
                ];
            }
            
            // Extract event date if it's an event file
            $eventDate = null;
            if ($category === 'events') {
                $eventDate = $this->extractEventDate($extractedText);
            }
            
            // Determine linked pages
            $linkedPages = $this->determineLinkedPages($category, $sourcePage);
            
            // Save to database
            $stmt = $this->pdo->prepare("
                INSERT INTO universal_files 
                (id, original_filename, stored_filename, file_path, uploaded_by, 
                 category, linked_pages, file_size, mime_type, extracted_text, event_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $linkedPagesJson = json_encode($linkedPages);
            $stmt->execute([
                $fileId,
                $file['name'],
                $storedFilename,
                $filePath,
                $uploadedBy,
                $category,
                $linkedPagesJson,
                $file['size'],
                $file['type'],
                $extractedText,
                $eventDate
            ]);
            
            return [
                'success' => true,
                'file_id' => $fileId,
                'category' => $category,
                'file_path' => $filePath,
                'linked_pages' => $linkedPages,
                'event_date' => $eventDate,
                'extracted_text' => $extractedText
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'No file uploaded or upload error'];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return ['valid' => false, 'error' => 'File too large. Maximum size: 10MB'];
        }
        
        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid file type. Allowed: PDF, DOCX, TXT, JPG, PNG'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Generate stored filename with category prefix
     */
    private function generateStoredFilename($fileId, $extension) {
        return $fileId . '.' . $extension;
    }
    
    /**
     * Categorize file based on content analysis
     */
    private function categorizeFile($text, $filename) {
        $text = strtolower($text . ' ' . $filename);
        
        // Define category keywords
        $keywords = [
            'events' => [
                'event', 'program', 'schedule', 'activity', 'forum', 'conference', 
                'exhibit', 'workshop', 'seminar', 'meeting', 'celebration', 'festival',
                'competition', 'contest', 'ceremony', 'gathering', 'assembly'
            ],
            'mou' => [
                'memorandum', 'understanding', 'agreement', 'partnership', 'collaboration',
                'mou', 'moa', 'cooperation', 'alliance', 'treaty', 'contract', 'accord'
            ],
            'awards' => [
                'award', 'recognition', 'leadership', 'excellence', 'best', 'global citizenship',
                'achievement', 'honor', 'prize', 'certificate', 'commendation', 'distinction',
                'merit', 'accolade', 'trophy', 'medal', 'plaque'
            ],
            'templates' => [
                'template', 'form', 'format', 'sample', 'application', 'checklist',
                'guide', 'instruction', 'manual', 'procedure', 'protocol', 'standard'
            ]
        ];
        
        // Count keyword matches for each category
        $scores = [];
        foreach ($keywords as $category => $categoryKeywords) {
            $score = 0;
            foreach ($categoryKeywords as $keyword) {
                $score += substr_count($text, $keyword);
            }
            $scores[$category] = $score;
        }
        
        // Return category with highest score, default to 'events' if no clear match
        $maxScore = max($scores);
        if ($maxScore > 0) {
            return array_search($maxScore, $scores);
        }
        
        return 'events'; // Default category
    }
    
    /**
     * Extract event date from text
     */
    private function extractEventDate($text) {
        // Date patterns: February 23, 2025 or 2025-02-23 or 02/23/2025
        $patterns = [
            '/(\w+)\s+(\d{1,2}),\s+(\d{4})/',  // February 23, 2025
            '/(\d{4})-(\d{1,2})-(\d{1,2})/',   // 2025-02-23
            '/(\d{1,2})\/(\d{1,2})\/(\d{4})/', // 02/23/2025
            '/(\d{1,2})-(\d{1,2})-(\d{4})/'    // 02-23-2025
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                try {
                    if (count($matches) === 4) {
                        if (strlen($matches[1]) > 2) {
                            // Month name format
                            $dateStr = $matches[1] . ' ' . $matches[2] . ', ' . $matches[3];
                            $date = DateTime::createFromFormat('F j, Y', $dateStr);
                        } else {
                            // Numeric format
                            if (strpos($pattern, '(\d{4})') === 0) {
                                // YYYY-MM-DD format
                                $dateStr = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
                            } else {
                                // MM/DD/YYYY or MM-DD-YYYY format
                                $dateStr = $matches[3] . '-' . $matches[1] . '-' . $matches[2];
                            }
                            $date = DateTime::createFromFormat('Y-m-d', $dateStr);
                        }
                        
                        if ($date && $date->format('Y-m-d') >= '2020-01-01') {
                            return $date->format('Y-m-d');
                        }
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Determine which pages should show this file
     */
    private function determineLinkedPages($category, $sourcePage) {
        $linkedPages = ['docs']; // Always include docs page
        
        switch ($category) {
            case 'events':
                $linkedPages = array_merge($linkedPages, ['events', 'scheduler', 'awards']);
                break;
            case 'mou':
                $linkedPages = array_merge($linkedPages, ['mou-moa']);
                break;
            case 'awards':
                $linkedPages = array_merge($linkedPages, ['awards']);
                break;
            case 'templates':
                $linkedPages = array_merge($linkedPages, ['templates']);
                break;
        }
        
        // Remove duplicates and ensure source page is included
        $linkedPages = array_unique(array_merge($linkedPages, [$sourcePage]));
        
        return array_values($linkedPages);
    }
    
    /**
     * Get files by category
     */
    public function getFilesByCategory($category = null, $status = 'active') {
        try {
            if ($category) {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM universal_files 
                    WHERE category = ? AND status = ?
                    ORDER BY upload_date DESC
                ");
                $stmt->execute([$category, $status]);
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM universal_files 
                    WHERE status = ?
                    ORDER BY upload_date DESC
                ");
                $stmt->execute([$status]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get file by ID
     */
    public function getFileById($fileId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM universal_files WHERE id = ?");
            $stmt->execute([$fileId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Update file category and linked pages
     */
    public function updateFileCategory($fileId, $newCategory, $linkedPages = null) {
        try {
            if ($linkedPages === null) {
                $linkedPages = $this->determineLinkedPages($newCategory, 'docs');
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE universal_files 
                SET category = ?, linked_pages = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $linkedPagesJson = json_encode($linkedPages);
            $stmt->execute([$newCategory, $linkedPagesJson, $fileId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Delete file (soft delete)
     */
    public function deleteFile($fileId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE universal_files 
                SET status = 'deleted', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$fileId]);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
