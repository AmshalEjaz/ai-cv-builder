<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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

    /**
     * OCR using pdftoppm + tesseract with robust binary resolution and logging.
     */
    private function extractPdfWithOcr(string $path): string
    {
        $pdftoppm = $this->resolveBinary('pdftoppm', config('services.ocr.pdftoppm', 'pdftoppm'));
        $tesseract = $this->resolveBinary('tesseract', config('services.ocr.tesseract', 'tesseract'));

        Log::info('FileParserService: resolved OCR binaries', ['pdftoppm' => $pdftoppm, 'tesseract' => $tesseract]);

        if (!$pdftoppm) {
            Log::error('FileParserService: pdftoppm not found');
            throw new \RuntimeException('Scanned PDF detected, but pdftoppm (Poppler) is not available. Install Poppler and/or set PDFTOPPM_BINARY in .env to the absolute path.');
        }
        if (!$tesseract) {
            Log::error('FileParserService: tesseract not found');
            throw new \RuntimeException('Scanned PDF detected, but Tesseract is not available. Install Tesseract and/or set TESSERACT_BINARY in .env to the absolute path.');
        }

        $workingDirectory = storage_path('app/ocr/' . uniqid('', true));
        File::ensureDirectoryExists($workingDirectory);
        $prefix = $workingDirectory . DIRECTORY_SEPARATOR . 'page';

        try {
          
            $renderCmd = sprintf('%s -png -r 300 %s %s', escapeshellarg($pdftoppm), escapeshellarg($path), escapeshellarg($prefix));
            $render = Process::run($renderCmd);
            Log::info('FileParserService: pdftoppm command', ['cmd' => $renderCmd, 'stdout' => $render->output(), 'stderr' => $render->errorOutput(), 'exitCode' => $render->exitCode()]);
            if ($render->failed()) {
                
                throw new \RuntimeException('Failed to run pdftoppm. Output: ' . $render->output() . ' ' . $render->errorOutput());
            }

            $pages = File::glob($prefix . '-*.png');
            if (empty($pages)) {
                Log::error('FileParserService: pdftoppm produced no images', ['prefix' => $prefix, 'files' => File::files($workingDirectory)]);
                throw new \RuntimeException('pdftoppm did not produce any images; cannot OCR.');
            }

            $textParts = [];
            foreach ($pages as $page) {
                $ocrCmd = sprintf('%s %s stdout -l eng', escapeshellarg($tesseract), escapeshellarg($page));
                $output = Process::run($ocrCmd);
                Log::info('FileParserService: tesseract command', ['cmd' => $ocrCmd, 'stdout_len' => strlen($output->output()), 'stderr' => $output->errorOutput(), 'exitCode' => $output->exitCode(), 'page' => $page]);
                if ($output->failed()) {
                    throw new \RuntimeException('Failed to run tesseract. Output: ' . $output->output() . ' ' . $output->errorOutput());
                }
                $pageText = trim($output->output());
                if ($pageText !== '') {
                    $textParts[] = $pageText;
                }
            }

            $result = trim(implode("\n", $textParts));
            if ($result === '') {
                Log::warning('FileParserService: OCR returned empty text', ['pages' => $pages]);
                throw new \RuntimeException('The uploaded PDF contains no readable text after OCR. Try a clearer scan or a text-based PDF/DOCX.');
            }

            return $result;
        } finally {
            try {
                File::deleteDirectory($workingDirectory);
            } catch (\Throwable $e) {
                Log::warning('FileParserService: failed to delete OCR working dir', ['dir' => $workingDirectory, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Resolve a binary path by checking environment overrides, common install paths,
     * and the system where/which command as a fallback.
     */
    private function resolveBinary(string $name, string $defaultCommand = null): ?string
    {
        $envKey = strtoupper($name) . '_BINARY';
        $envVal = env($envKey) ?: env('SERVICES_OCR_' . strtoupper($name) . '_BINARY');
        if ($envVal && File::exists($envVal)) {
            Log::info('FileParserService: binary resolved from env', ['name' => $name, 'path' => $envVal]);
            return $envVal;
        }

        $common = [];
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if ($name === 'pdftoppm') {
                $common = [
                    'C:\\ProgramData\\chocolatey\\lib\\poppler\\tools\\poppler\\bin\\pdftoppm.exe',
                    'C:\\tools\\poppler\\Library\\bin\\pdftoppm.exe',
                    'C:\\Program Files\\poppler\\bin\\pdftoppm.exe',
                ];
            } elseif ($name === 'tesseract') {
                $common = [
                    'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
                    'C:\\ProgramData\\chocolatey\\lib\\tesseract\\tools\\tesseract\\tesseract.exe',
                    'C:\\ProgramData\\chocolatey\\bin\\tesseract.exe',
                ];
            }
        } else {
          
            $common = [
                "/usr/bin/{$name}",
                "/usr/local/bin/{$name}",
            ];
        }

        foreach ($common as $p) {
            if (File::exists($p)) {
                Log::info('FileParserService: binary found in common path', ['name' => $name, 'path' => $p]);
                return $p;
            }
        }

        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $where = Process::run(['where.exe', $name]);
                if ($where->successful()) {
                    $line = strtok(trim($where->output()), "\r\n");
                    if ($line && File::exists($line)) {
                        Log::info('FileParserService: binary found via where.exe', ['name' => $name, 'path' => $line]);
                        return $line;
                    }
                } else {
                    Log::info('FileParserService: where.exe did not find the binary', ['name' => $name, 'output' => $where->output(), 'error' => $where->errorOutput()]);
                }
            } else {
                $which = Process::run(['which', $name]);
                if ($which->successful()) {
                    $line = trim($which->output());
                    if ($line && File::exists($line)) {
                        Log::info('FileParserService: binary found via which', ['name' => $name, 'path' => $line]);
                        return $line;
                    }
                } else {
                    Log::info('FileParserService: which did not find the binary', ['name' => $name, 'output' => $which->output(), 'error' => $which->errorOutput()]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('FileParserService: error while trying where/which', ['name' => $name, 'error' => $e->getMessage()]);
        }

        if ($defaultCommand) {
            try {
                $test = Process::run([$defaultCommand, '--version']);
                if ($test->successful()) {
                    Log::info('FileParserService: binary available via default command', ['name' => $name, 'cmd' => $defaultCommand]);
                    return $defaultCommand;
                }
            } catch (\Throwable $e) {
                // ignore
                Log::info('FileParserService: default command check failed', ['name' => $name, 'cmd' => $defaultCommand, 'error' => $e->getMessage()]);
            }
        }

        return null;
    }

    public function storeFile(UploadedFile $file, $userId): string
    {
        return $file->store("cvs/{$userId}", 'public');
    }
}