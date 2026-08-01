<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private $apiUrl;
    private $model;

    public function __construct()
    {
        $this->apiUrl = config('services.ollama.url', 'http://localhost:11434/api/generate');
        $this->model = config('services.ollama.model', 'llama2');
    }

    public function parseAndEnhanceCV(string $text): array
    {
        if (! config('services.ollama.enabled', false)) {
            return $this->parseLocally($text);
        }

        $prompt = $this->buildEnhancementPrompt($text);

        try {
            $response = Http::timeout(120)->post($this->apiUrl, [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false
            ]);

            if ($response->successful()) {
                $rawResponse = trim((string) $response->json('response'));
                $rawResponse = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $rawResponse) ?? $rawResponse;
                $result = json_decode(trim($rawResponse), true);

                if (! is_array($result)) {
                    throw new \RuntimeException('Ollama returned invalid CV JSON.');
                }

                return $this->validateAndFormatResponse($result);
            }

            return $this->getFallbackParsedData($text);
        } catch (\Exception $e) {
            Log::error('AI Service Error: ' . $e->getMessage());
            return $this->getFallbackParsedData($text);
        }
    }

    private function buildEnhancementPrompt(string $text): string
    {
        return <<<EOT
    You are a professional CV parser and enhancer. Parse the following CV text and return ONLY a valid JSON object with the following structure:

    {
        "name": "Full name",
        "title": "Current job title",
        "email": "Email address",
        "phone": "Phone number",
        "summary": "Professional summary (enhanced)",
        "skills": ["Skill1", "Skill2", ...],
        "experience": [
            {
                "company": "Company name",
                "position": "Job title",
                "start_date": "Start date",
                "end_date": "End date",
                "description": "Enhanced description with achievements"
            }
        ],
        "education": [
            {
                "institution": "School name",
                "degree": "Degree earned",
                "year": "Year completed"
            }
        ]
    }

    Rules:
    1. Enhance all descriptions to be more professional and achievement-oriented
    2. Standardize skill names (e.g., "php" becomes "PHP")
    3. Add relevant missing skills based on experience
    4. Make the summary compelling and results-driven

    CV Text:
    $text

    Return ONLY the JSON object, no other text.
    EOT;
    }

    private function validateAndFormatResponse(array $data): array
    {
        // Ensure all required fields exist
        $default = [
            'name' => '',
            'title' => '',
            'email' => '',
            'phone' => '',
            'summary' => '',
            'skills' => [],
            'experience' => [],
            'education' => []
        ];

        return array_merge($default, $data);
    }

    private function getFallbackParsedData(string $text): array
    {
        return $this->parseLocally($text);
    }

    private function parseLocally(string $text): array
    {
        $lines = array_values(array_filter(
            array_map(static fn(string $line): string => trim($line), preg_split('/\R/', $text) ?: []),
            static fn(string $line): bool => $line !== ''
        ));
        $plainText = implode("\n", $lines);
        $email = preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $plainText, $emailMatch)
            ? $emailMatch[0]
            : '';
        $phone = preg_match('/(?:\+?\d[\d\s().-]{7,}\d)/', $plainText, $phoneMatch)
            ? trim($phoneMatch[0])
            : '';
        $name = $lines[0] ?? '';
        $title = $lines[1] ?? '';
        $summary = $this->sectionText($lines, ['summary', 'profile', 'about me']);
        $skills = $this->sectionItems($lines, ['skills', 'technical skills', 'core skills']);
        $education = $this->sectionItems($lines, ['education', 'academic background']);

        return [
            'name' => trim($name),
            'title' => trim($title),
            'email' => $email,
            'phone' => $phone,
            'summary' => $summary ?: 'Professional with experience in a range of responsibilities and a commitment to delivering quality work.',
            'skills' => $skills,
            'experience' => [],
            'education' => $education,
        ];
    }

    private function sectionText(array $lines, array $headings): string
    {
        foreach ($lines as $index => $line) {
            if (in_array(strtolower(rtrim($line, ':')), $headings, true)) {
                $content = [];
                for ($next = $index + 1; isset($lines[$next]) && ! $this->isSectionHeading($lines[$next]); $next++) {
                    $content[] = $lines[$next];
                }
                return implode(' ', $content);
            }
        }

        return '';
    }

    private function sectionItems(array $lines, array $headings): array
    {
        foreach ($lines as $index => $line) {
            if (in_array(strtolower(rtrim($line, ':')), $headings, true)) {
                $items = [];
                for ($next = $index + 1; isset($lines[$next]) && ! $this->isSectionHeading($lines[$next]); $next++) {
                    $item = trim(ltrim($lines[$next], "-* \t"));
                    if ($item !== '') {
                        $items[] = $item;
                    }
                }
                return $items;
            }
        }

        return [];
    }

    private function isSectionHeading(string $line): bool
    {
        return in_array(strtolower(rtrim(trim($line), ':')), [
            'summary',
            'profile',
            'about me',
            'skills',
            'technical skills',
            'core skills',
            'experience',
            'work experience',
            'education',
            'academic background',
            'projects',
        ], true);
    }

    public function improveDescription(string $text, string $type = 'experience'): string
    {
        if (! config('services.ollama.enabled', false)) {
            return trim($text);
        }

        $prompt = $this->buildImprovementPrompt($text, $type);

        try {
            $response = Http::post($this->apiUrl, [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false
            ]);

            if ($response->successful()) {
                return trim($response->json()['response']);
            }

            return $text;
        } catch (\Exception $e) {
            Log::error('AI Improvement Error: ' . $e->getMessage());
            return $text;
        }
    }

    private function buildImprovementPrompt(string $text, string $type): string
    {
        $typeMap = [
            'experience' => 'job description with achievements and metrics',
            'summary' => 'professional summary',
            'skill' => 'skill name'
        ];

        $typeLabel = $typeMap[$type] ?? 'text';

        return <<<EOT
        Improve the following $typeLabel to be more professional, compelling, and results-oriented.
        Make it sound confident and achievement-focused.

        Original: $text

        Enhanced version (only the improved text, no explanation):
        EOT;
    }
}
