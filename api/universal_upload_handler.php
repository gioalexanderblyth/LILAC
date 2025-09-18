<?php
/**
 * Universal Upload Handler
 * Central repository for all uploaded files with intelligent categorization
 */

require_once __DIR__ . '/../config/database.php';

class UniversalUploadHandler {
    private $pdo;
    private $fileProcessor;
    private $allowedTypes = ['pdf', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB
    private $categories = ['events', 'mou', 'awards', 'templates'];
    
    public function __construct() {
        $db = new Database();
        $this->pdo = $db->getConnection();
        require_once __DIR__ . '/../classes/DynamicFileProcessor.php';
        $this->fileProcessor = new DynamicFileProcessor();
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
            
            // Define category before using it
            $category = 'docs'; // Default category
            
            // Use FileProcessor to handle the entire upload process
            $processResult = $this->fileProcessor->processFile($file, ['category' => $category]);
            
            if (!$processResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to process uploaded file: ' . ($processResult['error'] ?? 'Unknown error')
                ];
            }
            
            // Get the results from FileProcessor
            $fileId = $processResult['document_id'];
            $filePath = $processResult['file_path'];
            $extractedText = $processResult['extracted_content'] ?? '';
            
            // Determine categories based on content
            $detectedCategories = $this->categorizeFile($extractedText, $file['name']);
            
            // Always include 'docs' if uploaded from docs page, plus detected categories
            $allCategories = ['docs'];
            if (is_array($detectedCategories)) {
                $allCategories = array_unique(array_merge($allCategories, $detectedCategories));
            } else {
                $allCategories[] = $detectedCategories;
            }
            
            // Add award categories if any award criteria are detected
            $awardCategories = array_filter($detectedCategories, function($cat) {
                return strpos($cat, 'award_') === 0;
            });
            if (!empty($awardCategories)) {
                $allCategories[] = 'awards'; // Add general awards category
                $allCategories = array_unique($allCategories);
            }
            
            // Use the primary category for database storage (first detected category or docs)
            $primaryCategory = $detectedCategories[0] ?? 'docs';
            
            // Generate stored filename from the file path
            $storedFilename = basename($filePath);
            
            // Extract event date if it's an event file
            $eventDate = null;
            if (in_array('events', $allCategories)) {
                $eventDate = $this->extractEventDate($extractedText);
            }
            
            // Use all detected categories as linked pages
            $linkedPages = $allCategories;
            
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
                $primaryCategory,
                $linkedPagesJson,
                $file['size'],
                $file['type'],
                $extractedText,
                $eventDate
            ]);
            
            return [
                'success' => true,
                'file_id' => $fileId,
                'category' => $primaryCategory,
                'all_categories' => $allCategories,
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
        
        // Define category keywords with enhanced MOU detection and CHED Award Criteria
        $keywords = [
            'events' => [
                'event', 'program', 'schedule', 'activity', 'forum', 'conference', 
                'exhibit', 'workshop', 'seminar', 'meeting', 'celebration', 'festival',
                'competition', 'contest', 'ceremony', 'gathering', 'assembly'
            ],
            'mous' => [
                'memorandum', 'understanding', 'agreement', 'partnership', 'collaboration',
                'mou', 'moa', 'cooperation', 'alliance', 'treaty', 'contract', 'accord',
                'kuma', 'kuma-mou', 'memorandum of understanding', 'memorandum of agreement'
            ],
            'awards' => [
                'award', 'recognition', 'leadership', 'excellence', 'best', 'global citizenship',
                'achievement', 'honor', 'prize', 'certificate', 'commendation', 'distinction',
                'merit', 'accolade', 'trophy', 'medal', 'plaque'
            ],
            'templates' => [
                'template', 'form', 'format', 'sample', 'application', 'checklist',
                'guide', 'instruction', 'manual', 'procedure', 'protocol', 'standard'
            ],
            // CHED Award Criteria Categories
            'award_leadership' => [
                'champion bold innovation', 'cultivate global citizens', 'nurture lifelong learning',
                'lead with purpose', 'ethical and inclusive leadership', 'internationalization',
                'leadership', 'innovation', 'global citizens', 'lifelong learning', 'purpose',
                'ethical', 'inclusive', 'bold', 'champion', 'cultivate', 'nurture'
            ],
            'award_education' => [
                'expand access to global opportunities', 'foster collaborative innovation',
                'embrace inclusivity and beyond', 'international education', 'global opportunities',
                'collaborative innovation', 'inclusivity', 'education program', 'academic',
                'curriculum', 'international', 'global', 'opportunities', 'collaborative'
            ],
            'award_emerging' => [
                'innovation', 'strategic and inclusive growth', 'empowerment of others',
                'emerging leadership', 'strategic growth', 'inclusive growth', 'empowerment',
                'emerging', 'strategic', 'inclusive', 'growth', 'empower', 'mentoring'
            ],
            'award_regional' => [
                'comprehensive internationalization efforts', 'cooperation and collaboration',
                'measurable impact', 'regional office', 'internationalization efforts',
                'cooperation', 'collaboration', 'measurable impact', 'regional', 'office',
                'comprehensive', 'efforts', 'measurable', 'impact'
            ],
            'award_global' => [
                'ignite intercultural understanding', 'empower changemakers',
                'cultivate active engagement', 'global citizenship', 'intercultural understanding',
                'changemakers', 'active engagement', 'citizenship', 'intercultural',
                'understanding', 'changemakers', 'engagement', 'ignite', 'empower', 'cultivate'
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
        
        // Return all categories with scores > 0, sorted by score
        $detectedCategories = [];
        foreach ($scores as $category => $score) {
            if ($score > 0) {
                $detectedCategories[] = $category;
            }
        }
        
        // If no categories detected, return empty array (will default to docs only)
        return $detectedCategories;
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
