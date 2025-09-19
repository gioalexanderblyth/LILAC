<?php
require __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;

/**
 * Read file content dynamically based on extension
 */
function readFileContent($filePath) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    switch ($ext) {
        case 'txt':
            return file_get_contents($filePath);

        case 'pdf':
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($filePath);
                return $pdf->getText();
            } catch (Exception $e) {
                return "[PDF parse error] " . $e->getMessage();
            }

        case 'docx':
            try {
                $phpWord = IOFactory::load($filePath);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        }
                    }
                }
                return $text;
            } catch (Exception $e) {
                return "[DOCX parse error] " . $e->getMessage();
            }

        default:
            return "[Unsupported file type: $ext]";
    }
}

// ---- RUN TEST ----
$file = $argv[1] ?? null;

if (!$file || !file_exists($file)) {
    echo "Usage: php read_content_test.php <file>\n";
    exit(1);
}

echo "▶ Reading: $file\n\n";
$content = readFileContent($file);
echo "Extracted Content:\n";
echo "---------------------------------\n";
echo trim($content) ?: "[EMPTY CONTENT]";
echo "\n---------------------------------\n";
?>
