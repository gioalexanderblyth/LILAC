<?php
require __DIR__ . '/vendor/autoload.php'; // make sure composer autoload is included

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

function readFileContent($filePath) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $content = '';

    try {
        switch ($ext) {
            case 'txt':
                $content = file_get_contents($filePath);
                break;

            case 'pdf':
                $parser = new PdfParser();
                $pdf = $parser->parseFile($filePath);
                $content = $pdf->getText();
                break;

            case 'docx':
                $phpWord = IOFactory::load($filePath, 'Word2007');
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $content .= $element->getText() . " ";
                        }
                    }
                }
                break;

            default:
                $content = "[Unsupported file type: $ext]";
        }
    } catch (Exception $e) {
        $content = "[Error reading file: " . $e->getMessage() . "]";
    }

    return trim($content);
}

// ---- TEST ----
if (isset($argv[1])) {
    $file = $argv[1];
    if (!file_exists($file)) {
        echo "❌ File not found: $file\n";
        exit(1);
    }

    $content = readFileContent($file);
    echo "✅ File: $file\n";
    echo "📏 Length: " . strlen($content) . " characters\n";
    echo "🔍 Preview:\n" . substr($content, 0, 500) . "\n";
} else {
    echo "Usage: php test_file_reader.php <file-path>\n";
}
