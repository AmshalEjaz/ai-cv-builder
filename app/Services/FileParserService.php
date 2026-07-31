<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Http\UploadedFile;

class FileParserService
{
    public function extractText(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $path = $file->getRealPath();

        switch (strtolower($extension)) {
            case 'pdf':
                return $this->extractPdfText($path);
            case 'docx':
                return $this->extractDocxText($path);
            default:
                throw new \Exception('Unsupported file format');
        }
    }

    private function extractPdfText($path): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($path);

        return $pdf->getText();
    }

    private function extractDocxText($path): string
    {
        $phpWord = IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . ' ';
                }
            }
        }

        return $text;
    }

    public function storeFile(UploadedFile $file, $userId): string
    {
        return $file->store("cvs/{$userId}", 'public');
    }
}
