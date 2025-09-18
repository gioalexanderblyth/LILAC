<?php
/**
 * Dynamic File Processor using professional libraries
 * Handles different file types with appropriate extraction methods
 */

// Include the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;

class DynamicFileProcessor {
    private $pdo;
    private $logger;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->logger = new FileProcessorLogger();
    }
    
    /**
     * Process uploaded file and extract content based on file type
     */
    public function processFile($fileArray, $filePath) {
        $extension = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
        $extractedContent = '';
        $isReadable = true;
        
        try {
            switch ($extension) {
                // Text files
                case 'txt':
                case 'md':
                case 'log':
                    $extractedContent = $this->extractFromText($filePath);
                    break;
                    
                // PDF files
                case 'pdf':
                    $extractedContent = $this->extractFromPDF($filePath);
                    break;
                    
                // Word documents
                case 'docx':
                    $extractedContent = $this->extractFromDocx($filePath);
                    break;
                    
                case 'doc':
                    $extractedContent = $this->extractFromDoc($filePath);
                    break;
                    
                case 'rtf':
                    $extractedContent = $this->extractFromRtf($filePath);
                    break;
                    
                // Excel files (basic support)
                case 'csv':
                    $extractedContent = $this->extractFromCSV($filePath);
                    break;
                    
                // Web files
                case 'html':
                case 'htm':
                    $extractedContent = $this->extractFromHTML($filePath);
                    break;
                    
                case 'xml':
                    $extractedContent = $this->extractFromXML($filePath);
                    break;
                    
                case 'json':
                    $extractedContent = $this->extractFromJSON($filePath);
                    break;
                    
                // Archive files
                case 'zip':
                    $extractedContent = $this->extractFromZIP($filePath);
                    break;
                    
                // Image files (metadata only)
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                case 'bmp':
                    $extractedContent = $this->extractFromImage($filePath);
                    break;
                    
                default:
                    $extractedContent = $this->extractFromGeneric($filePath, $extension);
                    break;
            }
            
            // Validate extracted content
            if (empty($extractedContent) || strlen($extractedContent) < 10) {
                $isReadable = false;
                $this->logger->logExtractionFailure($fileArray['name'], $extension, 'No meaningful content extracted');
            }
            
        } catch (Exception $e) {
            $isReadable = false;
            $this->logger->logExtractionFailure($fileArray['name'], $extension, $e->getMessage());
            $extractedContent = '';
        }
        
        return [
            'content' => $extractedContent,
            'is_readable' => $isReadable,
            'file_type' => $extension
        ];
    }
    
    /**
     * Extract content from text files
     */
    private function extractFromText($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Text file not found: $filePath");
        }
        
        $content = file_get_contents($filePath);
        
        // Handle different encodings
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        return trim($content);
    }
    
    /**
     * Extract content from PDF files using smalot/pdfparser
     */
    private function extractFromPDF($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("PDF file not found: $filePath");
        }
        
        // Check if smalot/pdfparser is available
        if (class_exists('Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();
                
                if (!empty($text)) {
                    return trim($text);
                }
            } catch (Exception $e) {
                $this->logger->logExtractionFailure(basename($filePath), 'pdf', "PDF Parser error: " . $e->getMessage());
            }
        }
        
        // Fallback to our custom PDF extractor
        require_once __DIR__ . '/PDFTextExtractor.php';
        return PDFTextExtractor::extractText($filePath);
    }
    
    /**
     * Extract content from DOCX files using PHPWord
     */
    private function extractFromDocx($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("DOCX file not found: $filePath");
        }
        
        // Check if PHPWord is available
        if (class_exists('PhpOffice\PhpWord\IOFactory')) {
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                $text = '';
                
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        }
                    }
                }
                
                if (!empty($text)) {
                    return trim($text);
                }
            } catch (Exception $e) {
                $this->logger->logExtractionFailure(basename($filePath), 'docx', "PHPWord error: " . $e->getMessage());
            }
        }
        
        // Fallback to ZipArchive method
        return $this->extractFromDocxFallback($filePath);
    }
    
    /**
     * Fallback DOCX extraction using ZipArchive
     */
    private function extractFromDocxFallback($filePath) {
        if (!class_exists('ZipArchive')) {
            throw new Exception("ZipArchive not available for DOCX extraction");
        }
        
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
        
        throw new Exception("Failed to extract content from DOCX file");
    }
    
    /**
     * Extract content from DOC files (older format)
     */
    private function extractFromDoc($filePath) {
        // DOC files are more complex and would require additional libraries
        // For now, return empty content and log as unreadable
        $this->logger->logExtractionFailure(basename($filePath), 'doc', 'DOC format not supported - requires additional libraries');
        return '';
    }
    
    /**
     * Extract content from RTF files
     */
    private function extractFromRtf($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("RTF file not found: $filePath");
        }
        
        $content = file_get_contents($filePath);
        
        // Basic RTF text extraction (remove RTF formatting codes)
        $text = preg_replace('/\\[a-z]+[0-9]*\s?/', '', $content);
        $text = preg_replace('/[{}]/', '', $text);
        $text = preg_replace('/\\\\./', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Extract content from CSV files
     */
    private function extractFromCSV($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("CSV file not found: $filePath");
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }
        
        // Convert CSV to readable text
        $lines = explode("\n", $content);
        $text = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Replace commas with spaces for better readability
                $line = str_replace(',', ' ', $line);
                $text .= $line . ' ';
            }
        }
        
        return trim($text);
    }
    
    /**
     * Extract content from HTML files
     */
    private function extractFromHTML($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("HTML file not found: $filePath");
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }
        
        // Strip HTML tags and extract text
        $text = strip_tags($content);
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Extract content from XML files
     */
    private function extractFromXML($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("XML file not found: $filePath");
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }
        
        // Try to parse XML and extract text content
        try {
            $xml = simplexml_load_string($content);
            if ($xml !== false) {
                // Get all text content from XML
                $text = strip_tags($content);
                $text = preg_replace('/\s+/', ' ', $text);
                return trim($text);
            }
        } catch (Exception $e) {
            // If XML parsing fails, return raw content
        }
        
        // Fallback: strip tags and return
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
    
    /**
     * Extract content from JSON files
     */
    private function extractFromJSON($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("JSON file not found: $filePath");
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            return '';
        }
        
        // Try to parse JSON and extract text values
        try {
            $json = json_decode($content, true);
            if ($json !== null) {
                // Extract all string values from JSON
                $text = $this->extractTextFromArray($json);
                return trim($text);
            }
        } catch (Exception $e) {
            // If JSON parsing fails, return raw content
        }
        
        // Fallback: return raw content
        return trim($content);
    }
    
    /**
     * Extract content from ZIP files
     */
    private function extractFromZIP($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("ZIP file not found: $filePath");
        }
        
        if (!class_exists('ZipArchive')) {
            $this->logger->logExtractionFailure(basename($filePath), 'zip', 'ZipArchive extension not available');
            return '';
        }
        
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== TRUE) {
            $this->logger->logExtractionFailure(basename($filePath), 'zip', 'Cannot open ZIP file');
            return '';
        }
        
        $text = '';
        $fileCount = 0;
        
        // Extract text from first few text files in the archive
        for ($i = 0; $i < min($zip->numFiles, 10); $i++) {
            $filename = $zip->getNameIndex($i);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            // Only process text-based files
            if (in_array($extension, ['txt', 'md', 'html', 'htm', 'xml', 'json'])) {
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    $text .= $content . ' ';
                    $fileCount++;
                }
            }
        }
        
        $zip->close();
        
        if ($fileCount > 0) {
            return trim($text);
        } else {
            $this->logger->logExtractionFailure(basename($filePath), 'zip', 'No text files found in archive');
            return '';
        }
    }
    
    /**
     * Extract metadata from image files
     */
    private function extractFromImage($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Image file not found: $filePath");
        }
        
        // Get basic file information
        $info = getimagesize($filePath);
        $text = '';
        
        if ($info !== false) {
            $text .= "Image dimensions: " . $info[0] . "x" . $info[1] . " ";
            $text .= "Image type: " . $info['mime'] . " ";
        }
        
        // Get file metadata
        $text .= "File size: " . filesize($filePath) . " bytes ";
        
        // Try to get EXIF data if available
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($filePath);
            if ($exif !== false) {
                if (isset($exif['ImageDescription'])) {
                    $text .= "Description: " . $exif['ImageDescription'] . " ";
                }
                if (isset($exif['DateTime'])) {
                    $text .= "Date: " . $exif['DateTime'] . " ";
                }
            }
        }
        
        return trim($text);
    }
    
    /**
     * Helper method to extract text from array/object structures
     */
    private function extractTextFromArray($data) {
        $text = '';
        
        if (is_array($data) || is_object($data)) {
            foreach ($data as $value) {
                if (is_string($value)) {
                    $text .= $value . ' ';
                } elseif (is_array($value) || is_object($value)) {
                    $text .= $this->extractTextFromArray($value);
                }
            }
        } elseif (is_string($data)) {
            $text .= $data . ' ';
        }
        
        return $text;
    }
    
    /**
     * Generic extraction for other file types
     */
    private function extractFromGeneric($filePath, $extension) {
        // Try to read as text for common text-based formats
        $textExtensions = ['csv', 'json', 'xml', 'html', 'htm'];
        
        if (in_array($extension, $textExtensions)) {
            try {
                $content = file_get_contents($filePath);
                if ($content !== false) {
                    return trim($content);
                }
            } catch (Exception $e) {
                $this->logger->logExtractionFailure(basename($filePath), $extension, "Generic text extraction failed: " . $e->getMessage());
            }
        }
        
        // For binary files, return empty content
        $this->logger->logExtractionFailure(basename($filePath), $extension, 'Binary file format - no text extraction available');
        return '';
    }
}

/**
 * File Processor Logger
 * Logs extraction failures and successes
 */
class FileProcessorLogger {
    private $logFile;
    
    public function __construct() {
        $this->logFile = 'logs/file_processing.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function logExtractionFailure($filename, $fileType, $reason) {
        $logEntry = date('Y-m-d H:i:s') . " - EXTRACTION FAILED - File: $filename, Type: $fileType, Reason: $reason\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function logExtractionSuccess($filename, $fileType, $contentLength) {
        $logEntry = date('Y-m-d H:i:s') . " - EXTRACTION SUCCESS - File: $filename, Type: $fileType, Content Length: $contentLength\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>
