<?php

namespace App\Service;

interface PdfParserInterface
{
    public function execute(string $path, string $file, bool $test = false): string;
}
