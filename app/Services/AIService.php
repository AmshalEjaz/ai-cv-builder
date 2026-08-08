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

    public function parseAndEnhanceCV(string $text, ?string $template = null): array
    {
        if (! config('services.ollama.enabled', false)) {
            return $this->parseLocally($text);
        }

        $prompt = $this->buildEnhancementPrompt($text, $template);

        try {
            Log::info('AIService: sending prompt', ['template' => $template, 'prompt_preview' => mb_substr($prompt, 0, 240)]);

            // Send request adapted for Ollama v1 endpoints when configured
            $payload = [
                'model' => $this->model,
                // default legacy key 'prompt' for backward compatibility
                'prompt' => $prompt,
                'stream' => false,
            ];

            // If using a /v1/ endpoint, use 'input' as the main string and reduce randomness
            if (str_contains($this->apiUrl, '/v1/')) {
                $payload = [
                    'model' => $this->model,
                    'input' => $prompt,
                    'temperature' => 0,
                    'max_tokens' => 1500,
                ];
            }

            $response = Http::timeout(120)->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $body = $response->json();

                // Ollama v1 completions use choices[].text or choices[].text may be empty when streaming.
                $rawResponse = '';
                if (is_array($body) && isset($body['choices']) && is_array($body['choices']) && isset($body['choices'][0]['text'])) {
                    $rawResponse = trim((string) $body['choices'][0]['text']);
                } elseif (is_array($body) && isset($body['response'])) {
                    $rawResponse = trim((string) $body['response']);
                } else {
                    // Fallback: try top-level string
                    $rawResponse = is_string($response->body()) ? trim($response->body()) : '';
                }

                Log::info('AIService: raw response', ['response_preview' => mb_substr($rawResponse, 0, 240)]);

                $rawResponse = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $rawResponse) ?? $rawResponse;
                $result = json_decode(trim($rawResponse), true);

                if (! is_array($result)) {
                    // If the model did not return JSON, attempt a tolerant recovery: search for JSON substring
                    if (preg_match('/\{[\s\S]*\}/', $rawResponse, $m)) {
                        $try = json_decode($m[0], true);
                        if (is_array($try)) {
                            $result = $try;
                        }
                    }
                }

                // If still no structured response, try CLI fallback `ollama run <model>` when available
                if (! is_array($result) || empty($rawResponse)) {
                    try {
                        if (class_exists(\Illuminate\Support\Facades\Process::class)) {
                            Log::info('AIService: attempting CLI fallback to ollama run', ['model' => $this->model]);
                            $cli = trim((string) \Illuminate\Support\Facades\Process::run(['where', 'ollama'])->output());
                            if ($cli === '') {
                                // try common install path
                                $cli = 'C:\\Users\\User\\AppData\\Local\\Programs\\Ollama\\ollama.exe';
                            }

                            if (is_executable($cli) || file_exists($cli)) {
                                $escaped = str_replace('"', '\\"', $prompt);
                                $cmd = [$cli, 'run', $this->model, $escaped];
                                $proc = \Illuminate\Support\Facades\Process::run($cmd, timeout: 120);
                                $out = trim($proc->output());

                                if ($proc->successful() && $out !== '') {
                                    // Try to extract JSON from CLI output
                                    if (preg_match('/\{[\s\S]*\}/', $out, $m)) {
                                        $try = json_decode($m[0], true);
                                        if (is_array($try)) {
                                            $result = $try;
                                            $rawResponse = $out;
                                        }
                                    } else {
                                        // If output looks like JSON block on its own line, accept it
                                        $maybe = trim($out);
                                        $try2 = json_decode($maybe, true);
                                        if (is_array($try2)) {
                                            $result = $try2;
                                            $rawResponse = $out;
                                        }
                                    }
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('AIService CLI fallback failed', ['error' => $e->getMessage()]);
                    }
                }

                if (! is_array($result)) {
                    Log::warning('AIService: invalid JSON from model', ['raw' => mb_substr($rawResponse,0,1000)]);
                    return $this->getFallbackParsedData($text);
                }

                // Ensure the model didn't inject sample/template content by validating critical fields against the source text
                $result = $this->ensureResponseMatchesSource($result, $text);

                return $this->validateAndFormatResponse($result);
            }

            return $this->getFallbackParsedData($text);
        } catch (\Exception $e) {
            Log::error('AI Service Error: ' . $e->getMessage(), ['exception' => $e]);
            return $this->getFallbackParsedData($text);
        }
    }

    private function buildEnhancementPrompt(string $text, ?string $template = null): string
    {
        $templateNote = $template ? "Target template: {$template}. " : '';

        return <<<EOT
You are an expert resume parser and enhancer. {$templateNote}Read the messy CV text below and return ONLY a single valid JSON object in the exact schema shown.

IMPORTANT:
- Return only JSON. No markdown, headings, explanation, or extra text.
- Do not include any sample resume formatting or template markup.
- Remove duplicate sections and repeated content.
- Do not preserve or reuse any previously generated resume output, template placeholders, or unrelated text fragments.
- Never copy any sample resume names, companies, or sample template content from previous outputs.
- If the text appears to contain multiple CVs or duplicate candidate blocks, parse only the latest candidate information and discard the rest.
- Preserve the candidate's original contact details exactly as written.
- Normalize phone numbers, dates, and titles to clean presentable form.
- Split skills into a JSON array of distinct skill phrases.
- Normalize experience date ranges to either "Month YYYY - Present", "Month YYYY - Month YYYY", or a single year when that is all that is available.
- If the same information appears multiple times, keep the best version and discard duplicates.
- If a field cannot be identified, return an empty string or empty array.
- Handle messy or unstructured text gracefully.

Desired JSON schema:
{
  "name": "Full name",
  "title": "Current job title",
  "email": "Email address",
  "phone": "Phone number",
  "summary": "Professional summary (enhanced)",
  "skills": ["Skill 1", "Skill 2"],
  "experience": [
    {
      "company": "Company name",
      "position": "Job title",
      "start_date": "Month YYYY or Year",
      "end_date": "Month YYYY or Present",
      "description": "Short enhanced achievement-focused description"
    }
  ],
  "education": [
    {
      "institution": "School or university name",
      "degree": "Degree or program",
      "year": "Year completed or range"
    }
  ]
}

Example input and expected output:
Input:
1+123- 456- 7890 hello@reallygreatsite.com 123 Anywhere St., Any City

# Mohaib Ejaz

Human Research LORNA ALVARADO mohaibkhan86@gmail.com +923059211533 Sales Representative

## Professional Summary

With a Bachelor of Business Administration degree in Human Resource Management, and honing skills in critical analysis and marketing technology and global connectivity for organizational growth. My strong work ethic and ability to adapt to new challenges make me an asset in any team.

## SKILLS

Client Acquisition Conflict Resolution B2B Sales Negotiation Relationship Management Software Development

## WORK EXPERIENCE

Senior Sales Representative | Savvy Financial Services | January 2021 - Present
Created and implemented sales strategies that led to a 25% boost in annual revenue.

Sales Agent | Bold Design Studio | June 2017 - December 2018
Engaged in prospecting and qualifying leads via cold calling, email campaigns, and networking events.

## EDUCATION

University of Eastborough | Bachelor of Business Management | 2020 - 2023

Output:
{
  "name": "Mohaib Ejaz",
  "title": "Sales Representative",
  "email": "mohaibkhan86@gmail.com",
  "phone": "+923059211533",
  "summary": "Human Resources professional with a Bachelor of Business Administration in Human Resource Management. Strong communicator with experience driving recruitment, employee engagement, and process improvements.",
  "skills": ["Client Acquisition", "Conflict Resolution", "B2B Sales", "Negotiation", "Relationship Management", "Software Development"],
  "experience": [
    {
      "company": "Savvy Financial Services",
      "position": "Senior Sales Representative",
      "start_date": "January 2021",
      "end_date": "Present",
      "description": "Created and implemented sales strategies that increased annual revenue by 25% while managing key client relationships and improving team collaboration."
    },
    {
      "company": "Bold Design Studio",
      "position": "Sales Agent",
      "start_date": "June 2017",
      "end_date": "December 2018",
      "description": "Prospected and qualified leads through cold calling, email campaigns, and networking events, boosting sales by 20%."
    }
  ],
  "education": [
    {
      "institution": "University of Eastborough",
      "degree": "Bachelor of Business Management",
      "year": "2020 - 2023"
    }
  ]
}

Now parse and enhance this CV text and return ONLY the JSON object.

CV Text:
$text
EOT;
    }

    private function validateAndFormatResponse(array $data): array
    {
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

        $result = array_merge($default, $data);

        $result['name'] = trim((string) $result['name']);
        $result['title'] = trim((string) $result['title']);
        $result['email'] = trim((string) $result['email']);
        $result['phone'] = trim((string) $result['phone']);
        $result['summary'] = trim((string) $result['summary']);
        $result['skills'] = $this->normalizeSkills($result['skills']);

        $result['experience'] = array_values(array_filter(array_map([$this, 'normalizeExperienceEntry'], $result['experience'])));
        $result['experience'] = $this->deduplicateEntries($result['experience'], ['company', 'position', 'start_date', 'end_date']);

        $result['education'] = array_values(array_filter(array_map([$this, 'normalizeEducationEntry'], $result['education'])));
        $result['education'] = $this->deduplicateEntries($result['education'], ['institution', 'degree', 'year']);

        return $result;
    }

    private function deduplicateEntries(array $entries, array $keys): array
    {
        $seen = [];
        $deduplicated = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $hashParts = [];
            foreach ($keys as $key) {
                $hashParts[] = trim((string) ($entry[$key] ?? ''));
            }

            $hash = md5(implode('|', $hashParts));
            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $deduplicated[] = $entry;
        }

        return $deduplicated;
    }

    private function normalizeSkills($skills): array
    {
        if (! is_array($skills)) {
            if (is_string($skills)) {
                $skills = preg_split('/[;,\n\|]+/', $skills, -1, PREG_SPLIT_NO_EMPTY);
            } else {
                $skills = [];
            }
        }

        $cleanSkills = [];
        foreach ($skills as $skill) {
            $skill = trim((string) $skill);
            if ($skill === '') {
                continue;
            }
            $skill = preg_replace('/\s+/', ' ', $skill);
            $skill = $this->normalizeAcronyms($skill);
            $skill = mb_convert_case($skill, MB_CASE_TITLE, 'UTF-8');
            $cleanSkills[] = $skill;
        }

        return array_values(array_unique($cleanSkills));
    }

    private function normalizeAcronyms(string $text): string
    {
        $acronyms = ['PHP', 'SQL', 'CRM', 'HR', 'UI', 'UX', 'SaaS', 'B2B', 'B2C', 'AI', 'ERP', 'SEO', 'SEM'];

        return preg_replace_callback('/\b(' . implode('|', array_map('preg_quote', $acronyms)) . ')\b/i', static fn ($matches) => strtoupper($matches[1]), $text);
    }

    private function normalizeExperienceEntry($entry): array
    {
        if (! is_array($entry)) {
            return [];
        }

        $company = trim((string) ($entry['company'] ?? $entry['employer'] ?? ''));
        $position = trim((string) ($entry['position'] ?? $entry['title'] ?? ''));
        $description = trim((string) ($entry['description'] ?? $entry['summary'] ?? ''));
        $startDate = $this->normalizeDate((string) ($entry['start_date'] ?? $entry['startDate'] ?? ''));
        $endDate = $this->normalizeDate((string) ($entry['end_date'] ?? $entry['endDate'] ?? ''));

        if ($company === '' && $position === '' && $description === '') {
            return [];
        }

        return [
            'company' => $company,
            'position' => $position,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $description,
        ];
    }

    private function normalizeEducationEntry($entry): array
    {
        if (! is_array($entry)) {
            return [];
        }

        $institution = trim((string) ($entry['institution'] ?? $entry['school'] ?? $entry['university'] ?? ''));
        $degree = trim((string) ($entry['degree'] ?? $entry['program'] ?? ''));
        $year = trim((string) ($entry['year'] ?? $entry['date'] ?? ''));

        if ($institution === '' && $degree === '' && $year === '') {
            return [];
        }

        return [
            'institution' => $institution,
            'degree' => $degree,
            'year' => $this->normalizeYear($year),
        ];
    }

    private function normalizeYear(string $year): string
    {
        $year = trim(preg_replace('/\s+/', ' ', $year));
        if ($year === '') {
            return '';
        }

        if (preg_match('/^(\d{4})\s*[-–—]\s*(\d{4})$/', $year, $matches)) {
            return "{$matches[1]} - {$matches[2]}";
        }

        return $year;
    }

    private function normalizeDate(string $date): string
    {
        $date = trim(preg_replace('/\s+/', ' ', str_replace(['–', '—', '/'], '-', $date)));
        if ($date === '') {
            return '';
        }

        if (preg_match('/^(present|current)$/i', $date)) {
            return 'Present';
        }

        if (preg_match('/^(.*?)(?:\s*-\s*|\s+to\s+)(present|current)$/i', $date, $matches)) {
            $start = $this->normalizeDate(trim($matches[1]));
            return ($start !== '' ? $start . ' - ' : '') . 'Present';
        }

        if (preg_match('/^(\d{4})\s*-\s*(\d{1,2})$/', $date, $matches)) {
            $month = (int) $matches[2];
            if ($month >= 1 && $month <= 12) {
                return \DateTime::createFromFormat('n Y', "{$month} {$matches[1]}")->format('F Y');
            }
        }

        if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $date, $matches)) {
            return "{$matches[1]} - {$matches[2]}";
        }

        $formats = ['F Y', 'M Y', 'Y-m', 'Y-n', 'm/Y', 'n/Y', 'Y'];
        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $date);
            if ($parsed !== false) {
                if ($format === 'Y') {
                    return $parsed->format('Y');
                }

                return $parsed->format('F Y');
            }
        }

        // Preserve a cleaned version if parsing fails
        return preg_replace('/\s*-\s*/', ' - ', $date);
    }

    private function getFallbackParsedData(string $text): array
    {
        return $this->parseLocally($text);
    }

    /**
     * Ensure the AI-returned fields (especially name/email/phone) are actually present
     * in the source text. If not, prefer locally-extracted values from parseLocally().
     */
    private function ensureResponseMatchesSource(array $result, string $source): array
    {
        $fallback = $this->parseLocally($source);

        // Normalize source for simple containment checks
        $normSource = mb_strtolower(preg_replace('/\s+/', ' ', $source));

        // Email: ensure returned email exists in source (or fallback)
        if (! empty($result['email'])) {
            $email = mb_strtolower($result['email']);
            if (strpos($normSource, $email) === false) {
                $result['email'] = $fallback['email'] ?? '';
            }
        } else {
            $result['email'] = $fallback['email'] ?? '';
        }

        // Phone: simple digit-only match
        if (! empty($result['phone'])) {
            $cleanPhone = preg_replace('/[^0-9+]/', '', $result['phone']);
            if ($cleanPhone !== '' && strpos(preg_replace('/[^0-9+]/','', $normSource), preg_replace('/[^0-9+]/','',$cleanPhone)) === false) {
                $result['phone'] = $fallback['phone'] ?? '';
            }
        } else {
            $result['phone'] = $fallback['phone'] ?? '';
        }

        // Name: more heuristic — prefer AI name only if parts appear in source
        if (! empty($result['name'])) {
            $name = trim($result['name']);
            $pieces = preg_split('/\s+/', mb_strtolower($name));
            $found = false;
            foreach ($pieces as $p) {
                if ($p === '') continue;
                if (mb_strlen($p) >= 3 && strpos($normSource, $p) !== false) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $result['name'] = $fallback['name'] ?? '';
            }
        } else {
            $result['name'] = $fallback['name'] ?? '';
        }

        return $result;
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
