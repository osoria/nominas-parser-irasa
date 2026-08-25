<?php

namespace App\Service;

interface PdfParserInterface
{
    public function execute(string $path, string $file, string $mode, ?array $empleadosSelected, string $customEmail = ''): string;
}