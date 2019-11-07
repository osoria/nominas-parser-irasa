<?php

namespace App\Service;

use mikehaertl\pdftk\Pdf;

class PdfCut implements PdfCutInterface
{
    /** @var Pdf */
    private $pdf;

    public function init(string $pdfPath)
    {
        $this->pdf = new Pdf($pdfPath);
    }

    public function cut(int $page): Pdf
    {
        return $this->pdf->cat($page);
    }
}
