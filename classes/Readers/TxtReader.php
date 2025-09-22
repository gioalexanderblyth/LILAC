<?php
namespace LILAC\Files\Readers;

use LILAC\Files\FileReaderInterface;

class TxtReader implements FileReaderInterface
{
    public function read(string $filePath): array
    {
        try {
            $content = $this->readLargeFile($filePath);
            $content = $this->toUtf8($content);
            $trimmed = trim($content);
            if ($trimmed === '') {
                return [
                    'is_readable' => false,
                    'content' => '',
                    'error_message' => 'TXT file is empty'
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
                'error_message' => 'TXT read failed: ' . $e->getMessage()
            ];
        }
    }

    private function readLargeFile(string $filePath): string
    {
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open file');
        }
        $buffer = '';
        while (!feof($handle)) {
            $chunk = fread($handle, 8192);
            if ($chunk === false) break;
            $buffer .= $chunk;
            if (strlen($buffer) > 5 * 1024 * 1024) { // cap 5MB per TXT
                break;
            }
        }
        fclose($handle);
        return $buffer;
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


