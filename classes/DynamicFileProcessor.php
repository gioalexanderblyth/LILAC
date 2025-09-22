<?php
require_once __DIR__ . '/../vendor/autoload.php';

use LILAC\Files\FileReaderInterface;
use LILAC\Files\Readers\PdfReader;
use LILAC\Files\Readers\DocxReader;
use LILAC\Files\Readers\TxtReader;

class DynamicFileProcessor {
    private $logger;
    
    public function __construct() {
        $this->logger = new FileProcessorLogger(getenv('LILAC_LOG_PATH') ?: null);
    }
    
    /**
     * Wrapper method for compatibility with old FileProcessor interface
     */
    public function processFile($file, $additionalData = []) {
        try {
            // Process directly from the temporary upload path to avoid redundant I/O
            $tempPath = $file['tmp_name'] ?? null;
            if (empty($tempPath) || !file_exists($tempPath)) {
                throw new FileProcessingException('Temporary upload file not found');
            }

            // Process using the robust method against temp path
            $result = $this->processFileRobust($file, $tempPath);

            if ($result['is_readable']) {
                // Persist file only after successful processing
                $filePath = $this->saveFile($file);
                return [
                    'success' => true,
                    'document_id' => null, // Will be set by caller
                    'file_path' => $filePath,
                    'extracted_content' => $result['content'],
                    'content_length' => strlen($result['content'])
                ];
            } else {
                return [
                    'success' => false,
                    'error_message' => $result['error_message'] ?? 'File processing failed'
                ];
            }

        } catch (FileProcessingException $e) {
            return [
                'success' => false,
                'error_message' => $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error_message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Save uploaded file to disk
     */
    private function saveFile($file) {
        $uploadsDir = 'uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueFilename = 'doc_' . uniqid() . '.' . $extension;
        $filePath = $uploadsDir . $uniqueFilename;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Failed to save uploaded file");
        }
        
        return $filePath;
    }
    
    /**
     * Main file processing method - orchestrates dispatch to reader strategies
     */
    public function processFileRobust($fileArray, $filePath) {
        try {
            // Security checks
            $this->validateFileSecurity($filePath, $fileArray);
            
            // Validate file exists and is readable
            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new FileProcessingException("File not found or not readable: " . basename($filePath));
            }
            
            // Detect file type using MIME and extension as fallback
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $detectedType = $this->detectFileType($filePath, $extension);

            // Size guardrails (50MB cap already in validateFileSecurity)

            // Dispatch to specialized reader by MIME/type
            $reader = $this->resolveReader($filePath, $detectedType);
            if (!$reader) {
                throw new FileProcessingException("Unsupported file type: " . $detectedType);
            }

            $readResult = $reader->read($filePath);
            $extractedContent = $readResult['content'] ?? '';
            
            // Clean and validate content
            $extractedContent = trim($extractedContent);
            $isReadable = !empty($extractedContent);

            // Always prioritize content first, fallback only if truly empty
            $documentName = $fileArray['name'] ?? basename($filePath);
            $contentToCheck = $isReadable ? $extractedContent : $documentName;

            // Debug logging
            error_log("Extracted " . strlen($extractedContent) . " chars from $filePath → using " . ($isReadable ? "CONTENT" : "FILENAME"));

            // Debug: log immediate extraction outcome
            $this->logger->logSystemError('EXTRACTION_RESULT', [
                'file' => basename($filePath),
                'type' => $detectedType,
                'len' => strlen((string)$extractedContent),
                'preview' => substr((string)$extractedContent, 0, 50)
            ]);

            if ($isReadable) {
                $this->logger->logExtractionSuccess(basename($filePath), $detectedType, strlen($extractedContent));
            }

            // Category detection logging
            $this->logger->logSystemError('CATEGORY_DETECTION', [
                'file' => basename($filePath),
                'extracted_length' => strlen($extractedContent),
                'source' => ($isReadable ? 'content' : 'filename')
            ]);

            return [
                'content' => $extractedContent,
                'is_readable' => $isReadable,
                'file_type' => $detectedType,
                'processing_method' => 'success',
                'category_hints' => $this->detectContentCategory($contentToCheck)
            ];
            
        } catch (FileProcessingException $e) {
            // Handle file processing errors gracefully; keep partial content if available
            $this->logger->logExtractionFailure($fileArray['name'], $extension ?? 'unknown', $e->getMessage());
            error_log("Extraction failed for $filePath: " . $e->getMessage());
            
            // Check if we have any partial content
            $partial = isset($extractedContent) ? trim((string)$extractedContent) : '';
            $documentName = $fileArray['name'] ?? basename($filePath);
            
            if ($partial !== '') {
                // We have partial content, use it
                $contentToCheck = $partial;
                $this->logger->logSystemError('PARTIAL_CONTENT_USED', [
                    'file' => $fileArray['name'] ?? 'unknown',
                    'length' => strlen($partial)
                ]);
                return [
                    'content' => $partial,
                    'is_readable' => true,
                    'file_type' => $extension ?? 'unknown',
                    'processing_method' => 'partial_with_error',
                    'error_message' => $e->getMessage(),
                    'category_hints' => $this->detectContentCategory($contentToCheck)
                ];
            } else {
                // No content, fallback to filename
                $contentToCheck = $documentName;
                return [
                    'content' => '',
                    'is_readable' => false,
                    'file_type' => $extension ?? 'unknown',
                    'processing_method' => 'failed',
                    'error_message' => $e->getMessage(),
                    'category_hints' => $this->detectContentCategory($contentToCheck)
                ];
            }
            
        } catch (Exception $e) {
            // Handle unexpected errors; keep partial content if available
            $this->logger->logSystemError("Unexpected error in file processing", [
                'file' => $fileArray['name'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            error_log("Extraction failed for $filePath: " . $e->getMessage());
            
            // Check if we have any partial content
            $partial = isset($extractedContent) ? trim((string)$extractedContent) : '';
            $documentName = $fileArray['name'] ?? basename($filePath);
            
            if ($partial !== '') {
                // We have partial content, use it
                $contentToCheck = $partial;
                $this->logger->logSystemError('PARTIAL_CONTENT_USED', [
                    'file' => $fileArray['name'] ?? 'unknown',
                    'length' => strlen($partial)
                ]);
                return [
                    'content' => $partial,
                    'is_readable' => true,
                    'file_type' => $extension ?? 'unknown',
                    'processing_method' => 'partial_with_error',
                    'error_message' => $e->getMessage(),
                    'category_hints' => $this->detectContentCategory($contentToCheck)
                ];
            } else {
                // No content, fallback to filename
                $contentToCheck = $documentName;
                return [
                    'content' => '',
                    'is_readable' => false,
                    'file_type' => $extension ?? 'unknown',
                    'processing_method' => 'failed',
                    'error_message' => 'An unexpected error occurred while processing the file',
                    'category_hints' => $this->detectContentCategory($contentToCheck)
                ];
            }
        }
    }

    /**
     * Resolve a reader strategy by detected type or MIME
     */
    private function resolveReader(string $filePath, string $detectedType): ?FileReaderInterface
    {
        $mime = function_exists('mime_content_type') ? mime_content_type($filePath) : null;
        $map = [
            'application/pdf' => PdfReader::class,
            'text/plain' => TxtReader::class,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => DocxReader::class,
        ];
        $byType = [
            'pdf' => PdfReader::class,
            'txt' => TxtReader::class,
            'docx' => DocxReader::class,
        ];
        $class = $map[$mime] ?? $byType[$detectedType] ?? null;
        if ($class === null) return null;
        return new $class();
    }
    
    // Removed inline extractors in favor of reader strategies (TxtReader, PdfReader, DocxReader)
    
    /**
     * Extract from DOC files - fully native PHP implementation with prioritized content
     */
    private function extractFromDoc($filePath) {
        try {
            // Use comprehensive native PHP DOC parser
            $content = $this->parseDocFile($filePath);
            if (!empty(trim($content))) {
                return $content;
            }
        } catch (Exception $e) {
            $this->logger->logSystemError("DOC extraction failed: " . $e->getMessage(), ['file' => basename($filePath)]);
        }
        
        // Do not fallback to filename here; return empty so caller can decide
        return '';
    }
    
    /**
     * Comprehensive native PHP DOC file parser
     */
    private function parseDocFile($filePath) {
        $binaryContent = file_get_contents($filePath);
        if ($binaryContent === false) {
            throw new FileProcessingException("Could not read DOC file");
        }
        
        $text = '';
        
        // Method 1: Extract from WordDocument stream (main content)
        $text .= $this->extractFromWordDocumentStream($binaryContent);
        
        // Method 2: Extract from text streams in compound document
        $text .= $this->extractFromCompoundDocument($binaryContent);
        
        // Method 3: Extract readable ASCII sequences
        $text .= $this->extractReadableAscii($binaryContent);
        
        // Method 4: Extract from Unicode text blocks
        $text .= $this->extractUnicodeText($binaryContent);
        
        // Clean up the extracted text
        $text = $this->cleanExtractedText($text);
        
        return $text;
    }
    
    /**
     * Extract text from WordDocument stream
     */
    private function extractFromWordDocumentStream($binaryContent) {
        $text = '';
        
        // Look for WordDocument stream marker
        $wordDocPattern = '/WordDocument\0{4}/';
        if (preg_match($wordDocPattern, $binaryContent, $matches, PREG_OFFSET_CAPTURE)) {
            $startPos = $matches[0][1];
            $streamData = substr($binaryContent, $startPos, 8192); // First 8KB of stream
            
            // Extract text using multiple patterns
            $patterns = [
                '/[\x20-\x7E]{3,}/',           // ASCII text
                '/[\x00][\x20-\x7E]{2,}[\x00]/', // Null-terminated strings
                '/[\x20-\x7E]{1,}[\x00]{1,2}[\x20-\x7E]{1,}/' // Mixed text with nulls
            ];
            
            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $streamData, $matches);
                foreach ($matches[0] as $match) {
                    $cleanText = str_replace("\0", ' ', $match);
                    if (strlen($cleanText) > 2) {
                        $text .= $cleanText . ' ';
                    }
                }
            }
        }
        
        return $text;
    }
    
    /**
     * Extract text from compound document structure
     */
    private function extractFromCompoundDocument($binaryContent) {
        $text = '';
        
        // Look for compound document streams
        $streamPatterns = [
            '/SummaryInformation\0+/',
            '/DocumentSummaryInformation\0+/',
            '/1Table\0+/',
            '/0Table\0+/'
        ];
        
        foreach ($streamPatterns as $pattern) {
            if (preg_match_all($pattern, $binaryContent, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $startPos = $match[1];
                    $streamData = substr($binaryContent, $startPos, 4096);
                    
                    // Extract readable text from stream
                    preg_match_all('/[\x20-\x7E]{2,}/', $streamData, $textMatches);
                    foreach ($textMatches[0] as $textMatch) {
                        $text .= $textMatch . ' ';
                    }
                }
            }
        }
        
        return $text;
    }
    
    /**
     * Extract readable ASCII text sequences
     */
    private function extractReadableAscii($binaryContent) {
        $text = '';
        
        // Extract longer ASCII sequences (more likely to be meaningful)
        preg_match_all('/[\x20-\x7E]{5,}/', $binaryContent, $matches);
        foreach ($matches[0] as $match) {
            // Filter out sequences that look like binary data
            if (!$this->isLikelyBinaryData($match)) {
                $text .= $match . ' ';
            }
        }
        
        return $text;
    }
    
    /**
     * Extract Unicode text blocks
     */
    private function extractUnicodeText($binaryContent) {
        $text = '';
        
        // Look for Unicode text patterns (UTF-16LE)
        $unicodePattern = '/[\x20-\x7E]\x00[\x20-\x7E]\x00[\x20-\x7E]\x00[\x20-\x7E]\x00/';
        if (preg_match_all($unicodePattern, $binaryContent, $matches)) {
            foreach ($matches[0] as $match) {
                // Convert UTF-16LE to readable text
                $unicodeText = str_replace("\x00", '', $match);
                if (strlen($unicodeText) > 3) {
                    $text .= $unicodeText . ' ';
                }
            }
        }
        
        // Look for wide character strings
        preg_match_all('/[\x20-\x7E]\x00{3,}[\x20-\x7E]/', $binaryContent, $matches);
        foreach ($matches[0] as $match) {
            $cleanText = str_replace("\x00", '', $match);
            if (strlen($cleanText) > 2) {
                $text .= $cleanText . ' ';
            }
        }
        
        return $text;
    }
    
    /**
     * Check if text sequence is likely binary data
     */
    private function isLikelyBinaryData($text) {
        // Check for high ratio of non-printable characters
        $printableCount = preg_match_all('/[\x20-\x7E]/', $text);
        $ratio = $printableCount / strlen($text);
        
        // Check for repeated patterns (common in binary data)
        $hasRepeatedPatterns = preg_match('/(.)\1{3,}/', $text);
        
        // Check for excessive punctuation (unlikely in normal text)
        $punctCount = preg_match_all('/[!@#$%^&*()_+={}\[\]|\\:";\'<>?\/~`]/', $text);
        $punctRatio = $punctCount / strlen($text);
        
        return $ratio < 0.7 || $hasRepeatedPatterns || $punctRatio > 0.3;
    }
    
    /**
     * Clean and format extracted text
     */
    private function cleanExtractedText($text) {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove common DOC artifacts
        $artifacts = [
            '/\b[A-F0-9]{8}\b/',           // Hex addresses
            '/\b\d{4}-\d{2}-\d{2}\b/',     // Date artifacts
            '/\b\d{2}:\d{2}:\d{2}\b/',     // Time artifacts
            '/Microsoft\s*Word/',           // Software references
            '/\x00+/',                      // Null bytes
            '/[\x01-\x1F]/'                 // Control characters
        ];
        
        foreach ($artifacts as $pattern) {
            $text = preg_replace($pattern, ' ', $text);
        }
        
        // Final cleanup
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Extract from RTF files
     */
    private function extractFromRtf($filePath) {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileProcessingException("Could not read RTF file");
        }
        
        // Simple RTF text extraction (remove RTF formatting codes)
        $text = strip_tags($content);
        $text = preg_replace('/\\[a-z]+[0-9]*\s?/', '', $text);
        $text = preg_replace('/[{}]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (empty($text)) {
            throw new FileProcessingException("RTF file contains no extractable text");
        }
        
        return $text;
    }
    
    /**
     * Extract from CSV files
     */
    private function extractFromCsv($filePath) {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileProcessingException("Could not read CSV file");
        }
        
        // Convert CSV to readable text
        $lines = str_getcsv($content, "\n");
        $text = '';
        
        foreach ($lines as $line) {
            $fields = str_getcsv($line);
            $text .= implode(' | ', $fields) . "\n";
        }
        
        return trim($text);
    }
    
    /**
     * Extract from HTML files
     */
    private function extractFromHtml($filePath) {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileProcessingException("Could not read HTML file");
        }
        
        // Remove HTML tags and decode entities
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Extract from XML files
     */
    private function extractFromXml($filePath) {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileProcessingException("Could not read XML file");
        }
        
        // Simple XML text extraction
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Extract from JSON files
     */
    private function extractFromJson($filePath) {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new FileProcessingException("Could not read JSON file");
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FileProcessingException("Invalid JSON format");
        }
        
        // Convert JSON to readable text
        return $this->jsonToReadableText($data);
    }
    
    /**
     * Convert JSON data to readable text
     */
    private function jsonToReadableText($data, $indent = 0) {
        $text = '';
        $spaces = str_repeat('  ', $indent);
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($key)) {
                    $text .= $spaces . $key . ': ';
                }
                
                if (is_array($value) || is_object($value)) {
                    $text .= "\n" . $this->jsonToReadableText($value, $indent + 1);
                } else {
                    $text .= $value . "\n";
                }
            }
        } else {
            $text .= $spaces . $data . "\n";
        }
        
        return $text;
    }
    
    /**
     * Extract meaningful content from filename
     */
    private function extractFromFilename($filename) {
        // Remove file extension
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Replace underscores, hyphens, and dots with spaces
        $name = str_replace(['_', '-', '.'], ' ', $name);
        
        // Clean up multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);
        
        return trim($name);
    }
    
    // Removed old validateContent() in favor of simple non-empty check after trim
    
    /**
     * Validate file security - MIME type, size limits, and path sanitization
     */
    private function validateFileSecurity($filePath, $fileArray) {
        // Check file size (limit to 50MB)
        $maxSize = 50 * 1024 * 1024; // 50MB
        if (filesize($filePath) > $maxSize) {
            throw new FileProcessingException("File too large (maximum 50MB allowed)");
        }
        
        // Sanitize file path
        $realPath = realpath($filePath);
        if ($realPath === false) {
            throw new FileProcessingException("Invalid file path");
        }
        // Allow PHP's upload temp directory as a valid source path, or our uploads directory
        $uploadsRoot = realpath('uploads/');
        $tempDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $tempRoot = realpath($tempDir);
        $inUploads = $uploadsRoot && strpos($realPath, $uploadsRoot) === 0;
        $inTmp = $tempRoot && strpos($realPath, $tempRoot) === 0;
        if (!$inUploads && !$inTmp) {
            throw new FileProcessingException("Invalid file location");
        }
        
        // Validate MIME type against extension
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filePath);
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            $allowedMimes = [
                'txt' => ['text/plain', 'application/octet-stream'],
                'pdf' => ['application/pdf'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'doc' => ['application/msword'],
                'rtf' => ['application/rtf', 'text/rtf'],
                'csv' => ['text/csv', 'application/csv'],
                'html' => ['text/html'],
                'htm' => ['text/html'],
                'xml' => ['text/xml', 'application/xml'],
                'json' => ['application/json', 'text/json']
            ];
            
            if (isset($allowedMimes[$extension])) {
                if (!in_array($mimeType, $allowedMimes[$extension])) {
                    throw new FileProcessingException("File type mismatch - extension suggests {$extension} but MIME type is {$mimeType}");
                }
            }
        }
        
        // Check for suspicious file content (basic check)
        $content = file_get_contents($filePath, false, null, 0, 1024); // First 1KB
        if (strpos($content, '<?php') !== false || strpos($content, '<script') !== false) {
            throw new FileProcessingException("File contains potentially malicious content");
        }
    }
    
    /**
     * Centralized keyword detection for content categorization
     */
    private function detectContentCategory($content) {
        $content = strtolower($content);
        $hints = [];
        
        // MOU/Agreement keywords
        $mouKeywords = [
            'memorandum of understanding', 'memorandum of agreement', 'mou', 'moa',
            'agreement', 'collaboration', 'partnership', 'cooperation',
            'institution', 'university', 'college', 'student exchange',
            'international', 'global', 'research collaboration'
        ];
        
        // Awards keywords
        $awardKeywords = [
            'award', 'recognition', 'certificate', 'certification',
            'accreditation', 'achievement', 'honor', 'distinction',
            'excellence', 'quality', 'standard', 'compliance'
        ];
        
        // Events keywords
        $eventKeywords = [
            'conference', 'seminar', 'workshop', 'meeting', 'symposium',
            'event', 'activity', 'program', 'training', 'session'
        ];
        
        // Count keyword matches
        $mouCount = 0;
        foreach ($mouKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $mouCount++;
            }
        }
        
        $awardCount = 0;
        foreach ($awardKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $awardCount++;
            }
        }
        
        $eventCount = 0;
        foreach ($eventKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $eventCount++;
            }
        }
        
        // Determine primary category
        if ($mouCount >= 2) {
            $hints['primary'] = 'MOU';
            $hints['confidence'] = 'high';
        } elseif ($awardCount >= 2) {
            $hints['primary'] = 'Awards';
            $hints['confidence'] = 'high';
        } elseif ($eventCount >= 2) {
            $hints['primary'] = 'Events';
            $hints['confidence'] = 'high';
        } elseif ($mouCount >= 1) {
            $hints['primary'] = 'MOU';
            $hints['confidence'] = 'medium';
        } elseif ($awardCount >= 1) {
            $hints['primary'] = 'Awards';
            $hints['confidence'] = 'medium';
        } elseif ($eventCount >= 1) {
            $hints['primary'] = 'Events';
            $hints['confidence'] = 'medium';
        } else {
            $hints['primary'] = 'General';
            $hints['confidence'] = 'low';
        }
        
        $hints['mou_score'] = $mouCount;
        $hints['award_score'] = $awardCount;
        $hints['event_score'] = $eventCount;
        
        return $hints;
    }
    
    /**
     * Detect file type using multiple methods
     */
    private function detectFileType($filePath, $extension) {
        // First try MIME type detection
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filePath);
            if ($mimeType) {
                $mimeMap = [
                    'text/plain' => 'txt',
                    'application/pdf' => 'pdf',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'application/msword' => 'doc',
                    'application/rtf' => 'rtf',
                    'text/csv' => 'csv',
                    'text/html' => 'html',
                    'text/xml' => 'xml',
                    'application/json' => 'json'
                ];
                
                if (isset($mimeMap[$mimeType])) {
                    return $mimeMap[$mimeType];
                }
            }
        }
        
        // Check file signatures (magic bytes)
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            $header = fread($handle, 8);
            fclose($handle);
            
            // PDF signature
            if (substr($header, 0, 4) === '%PDF') {
                return 'pdf';
            }
            
            // DOC signature
            if (substr($header, 0, 8) === "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1") {
                return 'doc';
            }
            
            // DOCX signature (ZIP-based)
            if (substr($header, 0, 2) === 'PK') {
                return 'docx';
            }
        }
        
        // Fallback to extension
        return $extension;
    }
    
    // isCommandAvailable method removed - no longer needed with pure PHP implementation
}

/**
 * Custom exception for file processing errors
 */
class FileProcessingException extends Exception {
    public function __construct($message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Production-Ready File Processor Logger
 */
class FileProcessorLogger {
    private $logFile;
    private $isProduction;
    
    public function __construct($logFilePath = null) {
        $defaultPath = __DIR__ . '/../logs/file_processing.log';
        $envPath = getenv('LILAC_LOG_PATH');
        $this->logFile = $logFilePath ?: ($envPath ?: $defaultPath);
        $this->isProduction = $this->isProductionEnvironment();
    
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Determine if running in production environment
     */
    private function isProductionEnvironment() {
        // Check for common production indicators
        $productionIndicators = [
            $_SERVER['HTTP_HOST'] ?? '',
            $_SERVER['SERVER_NAME'] ?? '',
            $_ENV['APP_ENV'] ?? '',
            $_ENV['ENVIRONMENT'] ?? ''
        ];
        
        $productionKeywords = ['prod', 'production', 'live', 'www'];
        
        foreach ($productionIndicators as $indicator) {
            foreach ($productionKeywords as $keyword) {
                if (stripos($indicator, $keyword) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Log extraction failure (production-safe)
     */
    public function logExtractionFailure($filename, $fileType, $errorMessage) {
        if (!$this->isProduction) {
            // Development: Log detailed information
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = "[$timestamp] EXTRACTION_FAILED: $filename ($fileType) - $errorMessage\n";
            file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } else {
            // Production: Log minimal information
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = "[$timestamp] EXTRACTION_FAILED: " . basename($filename) . " ($fileType)\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Log extraction success (production-safe)
     */
    public function logExtractionSuccess($filename, $fileType, $contentLength) {
        if (!$this->isProduction) {
            // Development: Log detailed information
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = "[$timestamp] EXTRACTION_SUCCESS: $filename ($fileType) - $contentLength characters\n";
            file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } else {
            // Production: Log minimal information
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = "[$timestamp] EXTRACTION_SUCCESS: " . basename($filename) . " ($fileType)\n";
            file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Log system errors (always logged, production-safe)
     */
    public function logSystemError($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' - Context: ' . json_encode($context) : '';
        $logEntry = "[$timestamp] SYSTEM_ERROR: $message$contextStr\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>
