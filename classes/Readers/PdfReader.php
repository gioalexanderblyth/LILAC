<?php
namespace LILAC\Files\Readers;

use LILAC\Files\FileReaderInterface;
use Smalot\PdfParser\Parser as PdfParser;

class PdfReader implements FileReaderInterface
{
    public function read(string $filePath): array
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $text = (string)$pdf->getText();
            $text = $this->toUtf8($text);
            $trimmed = trim($text);
            if ($trimmed === '') {
                return [
                    'is_readable' => false,
                    'content' => '',
                    'error_message' => 'PDF contains no extractable text'
                ];
            }
            return [
                'is_readable' => true,
                'content' => $trimmed,
                'error_message' => null
            ];
        } catch (\Throwable $e) {
            return [
                'is_readable' => false,
                'content' => '',
                'error_message' => 'PDF extraction failed: ' . $e->getMessage()
            ];
        }
    }

    private function toUtf8(string $text): string
    {
        $encoding = mb_detect_encoding($text, ['UTF-8','ISO-8859-1','Windows-1252','UTF-16','UTF-32'], true) ?: 'UTF-8';
        if ($encoding !== 'UTF-8') {
            $converted = @mb_convert_encoding($text, 'UTF-8', $encoding);
            if ($converted !== false) { $text = $converted; }
        }
        return str_replace(["\r\n","\r"], "\n", $text);
    }
}
?>


