<?php
/**
 * MOU/MOA Data Validation Class
 * Provides comprehensive server-side validation for MOU/MOA data
 * to prevent XSS, injection attacks, and ensure data integrity
 */
class MouMoaValidator {
    
    // Validation rules and constraints
    const MAX_TITLE_LENGTH = 255;
    const MAX_DESCRIPTION_LENGTH = 2000;
    const MAX_PARTNER_NAME_LENGTH = 100;
    const MAX_FILE_NAME_LENGTH = 255;
    const ALLOWED_FILE_TYPES = ['pdf', 'doc', 'docx', 'txt'];
    const MAX_FILE_SIZE = 10485760; // 10MB
    
    /**
     * Validate MOU/MOA data
     * @param array $data Input data to validate
     * @return array Validation result with success status and errors
     */
    public static function validateMouData($data) {
        $errors = [];
        $sanitized = [];
        
        // Validate title
        if (isset($data['title'])) {
            $title = self::sanitizeString($data['title']);
            if (empty($title)) {
                $errors[] = 'Title is required';
            } elseif (strlen($title) > self::MAX_TITLE_LENGTH) {
                $errors[] = 'Title must be less than ' . self::MAX_TITLE_LENGTH . ' characters';
            } else {
                $sanitized['title'] = $title;
            }
        }
        
        // Validate partner name
        if (isset($data['partner_name'])) {
            $partnerName = self::sanitizeString($data['partner_name']);
            if (empty($partnerName)) {
                $errors[] = 'Partner name is required';
            } elseif (strlen($partnerName) > self::MAX_PARTNER_NAME_LENGTH) {
                $errors[] = 'Partner name must be less than ' . self::MAX_PARTNER_NAME_LENGTH . ' characters';
            } else {
                $sanitized['partner_name'] = $partnerName;
            }
        }
        
        // Validate description
        if (isset($data['description'])) {
            $description = self::sanitizeString($data['description']);
            if (strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
                $errors[] = 'Description must be less than ' . self::MAX_DESCRIPTION_LENGTH . ' characters';
            } else {
                $sanitized['description'] = $description;
            }
        }
        
        // Validate dates
        if (isset($data['start_date'])) {
            $startDate = self::validateDate($data['start_date']);
            if (!$startDate) {
                $errors[] = 'Invalid start date format';
            } else {
                $sanitized['start_date'] = $startDate;
            }
        }
        
        if (isset($data['end_date'])) {
            $endDate = self::validateDate($data['end_date']);
            if (!$endDate) {
                $errors[] = 'Invalid end date format';
            } else {
                $sanitized['end_date'] = $endDate;
            }
        }
        
        // Validate date logic
        if (isset($sanitized['start_date']) && isset($sanitized['end_date'])) {
            if (strtotime($sanitized['start_date']) > strtotime($sanitized['end_date'])) {
                $errors[] = 'End date must be after start date';
            }
        }
        
        // Validate file upload
        if (isset($data['file']) && is_array($data['file'])) {
            $fileValidation = self::validateFileUpload($data['file']);
            if (!$fileValidation['valid']) {
                $errors = array_merge($errors, $fileValidation['errors']);
            } else {
                $sanitized['file'] = $data['file'];
            }
        }
        
        // Validate status
        if (isset($data['status'])) {
            $validStatuses = ['Active', 'Expired', 'Pending', 'Cancelled'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors[] = 'Invalid status value';
            } else {
                $sanitized['status'] = $data['status'];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $sanitized
        ];
    }
    
    /**
     * Sanitize string input to prevent XSS
     * @param string $input Raw input string
     * @return string Sanitized string
     */
    private static function sanitizeString($input) {
        if (!is_string($input)) {
            return '';
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // HTML encode to prevent XSS
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Validate date format and value
     * @param string $date Date string to validate
     * @return string|false Valid date string or false
     */
    private static function validateDate($date) {
        if (!is_string($date) || empty($date)) {
            return false;
        }
        
        // Check if date is in valid format
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return false;
        }
        
        // Return formatted date
        return date('Y-m-d', $timestamp);
    }
    
    /**
     * Validate file upload
     * @param array $file $_FILES array element
     * @return array Validation result
     */
    private static function validateFileUpload($file) {
        $errors = [];
        
        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'File size must be less than ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB';
        }
        
        // Check file type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, self::ALLOWED_FILE_TYPES)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', self::ALLOWED_FILE_TYPES);
        }
        
        // Validate filename
        $fileName = self::sanitizeString($file['name']);
        if (strlen($fileName) > self::MAX_FILE_NAME_LENGTH) {
            $errors[] = 'Filename must be less than ' . self::MAX_FILE_NAME_LENGTH . ' characters';
        }
        
        // Check for malicious file content (basic check)
        if (self::containsMaliciousContent($file['tmp_name'])) {
            $errors[] = 'File contains potentially malicious content';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Basic malicious content detection
     * @param string $filePath Path to uploaded file
     * @return bool True if malicious content detected
     */
    private static function containsMaliciousContent($filePath) {
        if (!file_exists($filePath)) {
            return true;
        }
        
        // Read first 1KB of file
        $content = file_get_contents($filePath, false, null, 0, 1024);
        
        // Check for common malicious patterns
        $maliciousPatterns = [
            '/<script[^>]*>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/eval\(/i',
            '/document\.cookie/i'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate ID parameter
     * @param mixed $id ID to validate
     * @return int|false Valid ID or false
     */
    public static function validateId($id) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        return ($id !== false && $id > 0) ? $id : false;
    }
    
    /**
     * Validate search query
     * @param string $query Search query to validate
     * @return string Sanitized search query
     */
    public static function validateSearchQuery($query) {
        if (!is_string($query)) {
            return '';
        }
        
        // Remove dangerous characters but allow basic search terms
        $query = preg_replace('/[<>"\']/', '', $query);
        $query = trim($query);
        
        return substr($query, 0, 100); // Limit length
    }
}
