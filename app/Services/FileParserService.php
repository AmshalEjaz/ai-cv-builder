<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;

class FileParserService
{
    public function extractText(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $path = $file->getRealPath();

        switch (strtolower($extension)) {
            case 'pdf':
                $text = $this->extractPdfText($path);
                return trim($text) !== '' ? $text : $this->extractPdfWithOcr($path);
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

    private function extractPdfWithOcr(string $path): string
    {
        $pdftoppm = config('services.ocr.pdftoppm', 'pdftoppm');
        $tesseract = config('services.ocr.tesseract', 'tesseract');
        $workingDirectory = storage_path('app/ocr/' . uniqid('', true));
        File::ensureDirectoryExists($workingDirectory);
        $prefix = $workingDirectory . DIRECTORY_SEPARATOR . 'page';

        try {
            $render = Process::run(sprintf(
                '%s -png -r 180 %s %s',
                escapeshellarg($pdftoppm),
                escapeshellarg($path),
                escapeshellarg($prefix)
            ));
            if ($render->failed()) {
                throw new \RuntimeException('Scanned PDF detected, but pdftoppm is not available. Install Poppler to enable OCR.');
            }

            $text = [];
            foreach (File::glob($prefix . '-*.png') as $page) {
                $output = Process::run(sprintf(
                    '%s %s stdout -l eng',
                    escapeshellarg($tesseract),
                    escapeshellarg($page)
                ));
                if ($output->failed()) {
                    throw new \RuntimeException('Scanned PDF detected, but Tesseract is not available. Install Tesseract to enable OCR.');
                }
                $text[] = trim($output->output());
            }

            $result = trim(implode("\n", $text));
            if ($result === '') {
                throw new \RuntimeException('The uploaded PDF contains no readable text. Please upload a clearer CV.');
            }

            return $result;
        } finally {
            File::deleteDirectory($workingDirectory);
        }
    }

    public function storeFile(UploadedFile $file, $userId): string
    {
        return $file->store("cvs/{$userId}", 'public');
    }
}
