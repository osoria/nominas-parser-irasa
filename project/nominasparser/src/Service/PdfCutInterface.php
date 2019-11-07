<?php

namespace App\Service;

use mikehaertl\pdftk\Pdf;

interface PdfCutInterface
{
    public function init(string $pdfPath);

    public function cut(int $page): Pdf;
}
