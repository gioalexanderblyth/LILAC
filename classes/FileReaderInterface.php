<?php
namespace LILAC\Files;

interface FileReaderInterface
{
    /**
     * Read and extract text content from a file.
     *
     * @param string $filePath Absolute path to file on disk
     * @return array{is_readable:bool, content:string, error_message:?string}
     */
    public function read(string $filePath): array;
}
?>


