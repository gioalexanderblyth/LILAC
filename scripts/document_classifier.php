<?php
/**
 * Document Classification System for LILAC
 * 
 * This function automatically classifies uploaded documents into one of the following categories:
 * - Documents (default)
 * - Meetings
 * - Funds
 * - MOUs & MOAs
 * - Awards
 * - Templates
 * - Registrar Files
 * 
 * @param string $filename The filename to classify
 * @param string $contentSnippet Optional content snippet for additional analysis
 * @return string The classified document category
 */
function classifyDocument($filename, $contentSnippet = '') {
    $filename = strtoupper($filename);
    $content = strtoupper($contentSnippet);
    $combinedText = $filename . ' ' . $content;
    
    // Meetings
    if (strpos($combinedText, 'MEETING') !== false || 
        strpos($combinedText, 'MINUTES') !== false ||
        strpos($combinedText, 'AGENDA') !== false ||
        strpos($combinedText, 'SCHEDULE') !== false ||
        strpos($combinedText, 'APPOINTMENT') !== false ||
        strpos($combinedText, 'CONFERENCE') !== false ||
        strpos($combinedText, 'SEMINAR') !== false ||
        strpos($combinedText, 'WORKSHOP') !== false) {
        return 'Meetings';
    }
    
    // MOUs & MOAs
    if (strpos($combinedText, 'MOU') !== false || 
        strpos($combinedText, 'MOA') !== false ||
        strpos($combinedText, 'MEMORANDUM') !== false ||
        strpos($combinedText, 'AGREEMENT') !== false ||
        strpos($combinedText, 'PARTNERSHIP') !== false ||
        strpos($combinedText, 'COLLABORATION') !== false ||
        strpos($combinedText, 'CONTRACT') !== false ||
        strpos($combinedText, 'TREATY') !== false) {
        return 'MOUs & MOAs';
    }
    
    // Funds
    if (strpos($combinedText, 'BUDGET') !== false || 
        strpos($combinedText, 'FUND') !== false ||
        strpos($combinedText, 'FINANCIAL') !== false ||
        strpos($combinedText, 'EXPENSE') !== false ||
        strpos($combinedText, 'GRANT') !== false ||
        strpos($combinedText, 'DONATION') !== false ||
        strpos($combinedText, 'REVENUE') !== false ||
        strpos($combinedText, 'INCOME') !== false ||
        strpos($combinedText, 'COST') !== false ||
        strpos($combinedText, 'PAYMENT') !== false ||
        strpos($combinedText, 'INVOICE') !== false ||
        strpos($combinedText, 'RECEIPT') !== false) {
        return 'Funds';
    }
    
    // Awards
    if (strpos($combinedText, 'AWARD') !== false || 
        strpos($combinedText, 'CERTIFICATE') !== false ||
        strpos($combinedText, 'RECOGNITION') !== false ||
        strpos($combinedText, 'ACHIEVEMENT') !== false ||
        strpos($combinedText, 'HONOR') !== false ||
        strpos($combinedText, 'PRIZE') !== false ||
        strpos($combinedText, 'TROPHY') !== false ||
        strpos($combinedText, 'MEDAL') !== false ||
        strpos($combinedText, 'COMMENDATION') !== false ||
        strpos($combinedText, 'EXCELLENCE') !== false) {
        return 'Awards';
    }
    
    // Templates
    if (strpos($combinedText, 'TEMPLATE') !== false || 
        strpos($combinedText, 'FORMAT') !== false ||
        strpos($combinedText, 'SAMPLE') !== false ||
        strpos($combinedText, 'DRAFT') !== false ||
        strpos($combinedText, 'BLANK') !== false ||
        strpos($combinedText, 'OUTLINE') !== false ||
        strpos($combinedText, 'STRUCTURE') !== false ||
        strpos($combinedText, 'LAYOUT') !== false ||
        strpos($combinedText, 'FORM') !== false) {
        return 'Templates';
    }
    
    // Registrar Files
    if (strpos($combinedText, 'ADMISSION') !== false || 
        strpos($combinedText, 'ENROLLMENT') !== false ||
        strpos($combinedText, 'REGISTRATION') !== false ||
        strpos($combinedText, 'TRANSCRIPT') !== false ||
        strpos($combinedText, 'DIPLOMA') !== false ||
        strpos($combinedText, 'PERSONAL') !== false ||
        strpos($combinedText, 'HISTORY') !== false ||
        strpos($combinedText, 'STUDENT') !== false ||
        strpos($combinedText, 'FOREIGN') !== false ||
        strpos($combinedText, 'INTERNATIONAL') !== false ||
        strpos($combinedText, 'REGISTRAR') !== false ||
        strpos($combinedText, 'ACADEMIC') !== false ||
        strpos($combinedText, 'COURSE') !== false ||
        strpos($combinedText, 'GRADE') !== false ||
        strpos($combinedText, 'CREDIT') !== false ||
        strpos($combinedText, 'DEGREE') !== false ||
        strpos($combinedText, 'APPLICATION') !== false) {
        return 'Registrar Files';
    }
    
    // Default to Documents for general documents
    return 'Documents';
}

// Example usage and testing
if (isset($_GET['test'])) {
    $testFiles = [
        'KUMA-MOU.pdf',
        'PERSONAL-HISTORY-STATEMENT-2.pdf', 
        'ADMISSION-FORM-FOR-FOREIGN-STUDENT-2024-2.pdf',
        'MEETING-MINUTES-2024.pdf',
        'BUDGET-REPORT-2024.pdf',
        'AWARD-CERTIFICATE.pdf',
        'TEMPLATE-FORM.pdf',
        'GENERAL-DOCUMENT.pdf'
    ];
    
    echo "<h2>Document Classification Test Results</h2>";
    foreach ($testFiles as $file) {
        $category = classifyDocument($file);
        echo "<p><strong>$file</strong> → <em>$category</em></p>";
    }
}
?> 