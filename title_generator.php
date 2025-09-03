<?php
/**
 * Generate a human-readable document title from filename
 * @param string $filename The original filename
 * @return string A formatted title
 */
function generateDocumentTitle($filename) {
    // Remove file extension
    $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    
    // Replace common separators with spaces
    $title = str_replace(['-', '_', '.', '  '], ' ', $nameWithoutExt);
    
    // Clean up multiple spaces
    $title = preg_replace('/\s+/', ' ', $title);
    
    // Capitalize words properly
    $title = ucwords(strtolower($title));
    
    // Handle common abbreviations and special cases
    $title = str_replace('Mou', 'MOU', $title);
    $title = str_replace('Moa', 'MOA', $title);
    $title = str_replace('Cpu', 'CPU', $title);
    $title = str_replace('Kuma', 'KUMA', $title);
    
    // Add context based on keywords
    if (stripos($filename, 'MOU') !== false || stripos($filename, 'MOA') !== false) {
        if (stripos($title, 'MOU') === false && stripos($title, 'MOA') === false) {
            $title = "MOU - " . $title;
        }
    }
    
    if (stripos($filename, 'PERSONAL-HISTORY') !== false) {
        $title = "Personal History Statement";
    }
    
    if (stripos($filename, 'ADMISSION') !== false) {
        $title = "Admission Form for Foreign Student";
    }
    
    return trim($title);
}

// Test the title generation
$testFiles = [
    'KUMA-MOU.pdf',
    'PERSONAL-HISTORY-STATEMENT-2.pdf',
    'ADMISSION-FORM-FOR-FOREIGN-STUDENT-2024-2.pdf',
    'Q1_Budget_Report.pdf',
    'Employee_of_the_Year_Certificate.pdf',
    'Meeting_Minutes_2024.pdf'
];

echo "<h2>Automatic Title Generation Test</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'><th>Original Filename</th><th>Generated Title</th></tr>";

foreach ($testFiles as $file) {
    $title = generateDocumentTitle($file);
    echo "<tr>";
    echo "<td style='padding: 8px;'>$file</td>";
    echo "<td style='padding: 8px;'><strong>$title</strong></td>";
    echo "</tr>";
}

echo "</table>";
?> 