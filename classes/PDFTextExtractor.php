<?php
/**
 * Professional PDF Text Extractor using smalot/pdfparser
 * Extracts actual text content from PDF files without relying on filenames
 */

// Include the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Smalot\PdfParser\Parser;

class PDFTextExtractor {
    
    /**
     * Extract text from PDF file using professional PDF parser
     */
    public static function extractText($filePath) {
        if (!file_exists($filePath)) {
            return '';
        }
        
        try {
            // Use professional PDF parser
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            // Clean up the text
            $text = self::cleanExtractedText($text);
            
            // If professional parser fails or returns minimal text, try fallback methods
            if (strlen($text) < 50) {
                $text = self::extractTextFallback($filePath);
            }
            
            return trim($text);
            
        } catch (Exception $e) {
            // If professional parser fails, use fallback methods
            error_log("PDF Parser Error: " . $e->getMessage());
            return self::extractTextFallback($filePath);
        }
    }
    
    /**
     * Clean and normalize extracted text
     */
    private static function cleanExtractedText($text) {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove non-printable characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Remove common PDF artifacts
        $text = preg_replace('/\b\d+\s*$/', '', $text); // Remove trailing numbers
        $text = preg_replace('/^\d+\s*/', '', $text); // Remove leading numbers
        
        return trim($text);
    }
    
    /**
     * Fallback extraction methods for when professional parser fails
     */
    private static function extractTextFallback($filePath) {
        // Method 1: Try PDF text extraction using regex patterns
        $text = self::extractTextFromPDF($filePath);
        
        // Method 2: If that fails, try alternative extraction methods
        if (strlen($text) < 50) {
            $text = self::extractTextAlternative($filePath);
        }
        
        // Method 3: If still no good content, try binary text extraction
        if (strlen($text) < 50) {
            $text = self::extractTextFromBinary($filePath);
        }
        
        return trim($text);
    }
    
    /**
     * Extract text using PDF structure analysis
     */
    private static function extractTextFromPDF($filePath) {
        $content = file_get_contents($filePath);
        $text = '';
        
        // Look for text objects in PDF
        if (preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches)) {
            foreach ($matches[1] as $textBlock) {
                // Extract text from parentheses
                if (preg_match_all('/\((.*?)\)/s', $textBlock, $textMatches)) {
                    foreach ($textMatches[1] as $textContent) {
                        $text .= $textContent . ' ';
                    }
                }
                
                // Extract text from angle brackets (hex encoded)
                if (preg_match_all('/<([0-9A-Fa-f]+)>/s', $textBlock, $hexMatches)) {
                    foreach ($hexMatches[1] as $hexText) {
                        $decoded = self::hexToString($hexText);
                        if (strlen($decoded) > 2) {
                            $text .= $decoded . ' ';
                        }
                    }
                }
            }
        }
        
        // Look for stream objects containing text
        if (preg_match_all('/stream\s+(.*?)\s+endstream/s', $content, $streamMatches)) {
            foreach ($streamMatches[1] as $stream) {
                // Decompress if needed
                $decompressed = @gzuncompress($stream);
                if ($decompressed !== false) {
                    $stream = $decompressed;
                }
                
                // Extract readable text from stream
                if (preg_match_all('/[A-Za-z]{3,}/', $stream, $wordMatches)) {
                    $words = array_filter($wordMatches[0], function($word) {
                        return strlen($word) > 2 && !preg_match('/^[0-9]+$/', $word);
                    });
                    $text .= implode(' ', array_slice($words, 0, 100)) . ' ';
                }
            }
        }
        
        return $text;
    }
    
    /**
     * Alternative text extraction method
     */
    private static function extractTextAlternative($filePath) {
        $content = file_get_contents($filePath);
        $text = '';
        
        // Look for text patterns in the entire PDF
        $patterns = [
            // Common text patterns
            '/[A-Za-z]{4,}/',
            // Text between common delimiters
            '/\b[A-Za-z]{3,}\b/',
            // Text in quotes
            '/"([^"]+)"/',
            // Text in parentheses
            '/\(([^)]+)\)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] ?? $matches[0] as $match) {
                    if (strlen($match) > 3 && !preg_match('/^[0-9\s]+$/', $match)) {
                        $text .= $match . ' ';
                    }
                }
            }
        }
        
        // Remove duplicates and clean up
        $words = array_unique(explode(' ', $text));
        $text = implode(' ', array_slice($words, 0, 200));
        
        return $text;
    }
    
    /**
     * Extract text from binary PDF content
     */
    private static function extractTextFromBinary($filePath) {
        $content = file_get_contents($filePath);
        $text = '';
        
        // Convert binary to readable text where possible
        $readableChars = '';
        for ($i = 0; $i < strlen($content); $i++) {
            $char = $content[$i];
            $ascii = ord($char);
            
            // Keep printable ASCII characters
            if ($ascii >= 32 && $ascii <= 126) {
                $readableChars .= $char;
            } elseif ($ascii == 10 || $ascii == 13) {
                $readableChars .= ' ';
            }
        }
        
        // Extract words from readable characters
        if (preg_match_all('/[A-Za-z]{3,}/', $readableChars, $matches)) {
            $words = array_filter($matches[0], function($word) {
                return strlen($word) > 2 && !preg_match('/^[0-9]+$/', $word);
            });
            $text = implode(' ', array_slice($words, 0, 150));
        }
        
        return $text;
    }
    
    /**
     * Convert hex string to readable text
     */
    private static function hexToString($hex) {
        $text = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $byte = substr($hex, $i, 2);
            $char = chr(hexdec($byte));
            if (ord($char) >= 32 && ord($char) <= 126) {
                $text .= $char;
            }
        }
        return $text;
    }
    
    /**
     * Check if extracted text is meaningful
     */
    public static function isMeaningfulText($text) {
        if (strlen($text) < 20) {
            return false;
        }
        
        // Check for common meaningful words
        $meaningfulWords = [
            'agreement', 'contract', 'memorandum', 'understanding', 'partnership',
            'collaboration', 'international', 'education', 'research', 'university',
            'college', 'institution', 'program', 'course', 'study', 'learning',
            'teaching', 'academic', 'curriculum', 'scholarship', 'exchange',
            'cooperation', 'global', 'worldwide', 'cultural', 'community',
            'leadership', 'management', 'administration', 'coordination'
        ];
        
        $lowerText = strtolower($text);
        $foundWords = 0;
        
        foreach ($meaningfulWords as $word) {
            if (strpos($lowerText, $word) !== false) {
                $foundWords++;
            }
        }
        
        return $foundWords >= 2;
    }
    
    /**
     * Get extraction quality score
     */
    public static function getExtractionQuality($text) {
        if (strlen($text) < 20) {
            return 0;
        }
        
        $score = 0;
        
        // Length score (max 40 points)
        $score += min(40, strlen($text) / 5);
        
        // Word count score (max 30 points)
        $wordCount = str_word_count($text);
        $score += min(30, $wordCount / 2);
        
        // Meaningful content score (max 30 points)
        if (self::isMeaningfulText($text)) {
            $score += 30;
        }
        
        return min(100, $score);
    }
}
?>