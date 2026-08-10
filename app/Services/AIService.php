<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $apiUrl;
    private string $model;

    public function __construct()
    {
        $this->apiUrl = config('services.ollama.url', 'http://localhost:11434/api/generate');
        $this->model = config('services.ollama.model', 'llama2');
    }

    /**
     * Extract the user's CV into structured data.
     *
     * Important: this method is an extractor, not a creative resume writer.
     * Ollama is explicitly told to use only the supplied CV text. A deterministic
     * source-based fallback is also used for fields such as contact details and
     * skills so a weak/local model cannot turn them into null or invented values.
     */
    public function parseAndEnhanceCV(string $text, ?string $template = null): array
    {
        @set_time_limit(310);

        $text = trim($text);
        $sourceData = $this->parseLocally($text);

        if ($text === '') {
            return $sourceData;
        }

        if (! config('services.ollama.enabled', false)) {
            return $sourceData;
        }

        $prompt = $this->buildEnhancementPrompt($text, $template);

        try {
            Log::info('AIService: sending CV extraction prompt', [
                'template' => $template,
                'model' => $this->model,
                'source_length' => mb_strlen($text),
            ]);

            $payload = [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
                'options' => [
                    'temperature' => 0,
                    'num_ctx' => 16384,
                    'num_predict' => 4096,
                ],
            ];

            // Keep compatibility with Ollama-compatible /v1 endpoints.
            if (str_contains($this->apiUrl, '/v1/')) {
                $payload = [
                    'model' => $this->model,
                    'input' => $prompt,
                    'temperature' => 0,
                    'max_tokens' => 4000,
                ];
            }

            $response = Http::timeout(300)->post($this->apiUrl, $payload);

            if (! $response->successful()) {
                Log::warning('AIService: Ollama request failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 1000),
                ]);

                return $sourceData;
            }

            $body = $response->json();
            $rawResponse = $this->extractModelText($body, $response->body());

            Log::info('AIService: raw response received', [
                'response_preview' => mb_substr($rawResponse, 0, 1000),
            ]);

            $result = $this->decodeJsonResponse($rawResponse);

            if (! is_array($result)) {
                Log::warning('AIService: invalid JSON from Ollama', [
                    'raw' => mb_substr($rawResponse, 0, 2000),
                ]);

                return $sourceData;
            }

            // First normalize the model result, then force critical source facts
            // back to values that actually exist in the uploaded CV.
            $result = $this->validateAndFormatResponse($result);
            $result = $this->ensureResponseMatchesSource($result, $text, $sourceData);

            return $this->validateAndFormatResponse($result);
        } catch (\Throwable $e) {
            Log::error('AI Service Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $sourceData;
        }
    }

    private function buildEnhancementPrompt(string $text, ?string $template = null): string
    {
        $templateNote = $template
            ? "The selected visual template is '{$template}'. It affects presentation only; NEVER use template/sample content as CV data."
            : 'The visual template is presentation-only; NEVER use template/sample content as CV data.';

        return <<<EOT
You are a STRICT CV DATA EXTRACTION ENGINE.

{$templateNote}

Your only job is to extract and lightly clean information from the supplied user's CV text.
Do NOT invent, guess, assume, autocomplete, or add information.
Do NOT use your general knowledge to fill missing CV fields.
Do NOT create a better/fake work history.
Do NOT create companies, dates, degrees, skills, projects, languages, emails, phone numbers, locations, or achievements that are not supported by the source text.

SOURCE-OF-TRUTH RULES:
1. The CV text below is the ONLY source of candidate information.
2. If a field is not present or cannot be confidently identified, return an empty string or empty array.
3. Preserve names, email addresses, phone numbers, company names, institution names, dates and skills from the source. You may clean obvious whitespace/OCR noise but must not change their meaning.
4. Skills must be extracted from the CV's skills/technical-skills/core-skills sections and may also include clearly listed technologies in other sections. Return every distinct skill actually present. Never return null for skills; use [] only when no skill is present anywhere in the source.
5. Do not turn section headings such as "Languages & Frameworks" or "Web Technologies" into skills. Extract the items underneath them.
6. Work experience must contain only jobs/employers actually present in the source. Do not infer dates or employers from projects.
7. Projects must contain only projects explicitly named/described in the source.
8. Education must contain only education explicitly present in the source.
9. Languages must contain only human languages explicitly mentioned as languages. Programming languages belong in skills, not human languages.
10. Summary may be lightly rewritten for professionalism, but every factual statement must be supported by the source.
11. Keep all relevant information. Do not silently drop sections merely because the schema has optional fields.
12. Return ONLY valid JSON. No markdown fences and no explanation.

Return exactly this JSON structure:
{
  "name": "",
  "email": "",
  "phone": "",
  "location": "",
  "title": "",
  "summary": "",
  "skills": [],
  "experience": [
    {
      "company": "",
      "position": "",
      "start_date": "",
      "end_date": "",
      "description": ""
    }
  ],
  "education": [
    {
      "institution": "",
      "degree": "",
      "year": "",
      "grade": ""
    }
  ],
  "projects": [
    {
      "name": "",
      "description": "",
      "technologies": []
    }
  ],
  "certifications": [],
  "languages": [
    {
      "language": "",
      "proficiency": ""
    }
  ],
  "achievements": [],
  "volunteer": [],
  "references": []
}

CV TEXT START
--------------------
{$text}
--------------------
CV TEXT END

Return the JSON object now.
EOT;
    }

    private function extractModelText($body, string $rawBody): string
    {
        if (is_array($body) && isset($body['choices'][0]['text'])) {
            return trim((string) $body['choices'][0]['text']);
        }

        if (is_array($body) && isset($body['choices'][0]['message']['content'])) {
            return trim((string) $body['choices'][0]['message']['content']);
        }

        if (is_array($body) && isset($body['response'])) {
            return trim((string) $body['response']);
        }

        if (is_array($body) && isset($body['message']['content'])) {
            return trim((string) $body['message']['content']);
        }

        return trim($rawBody);
    }

    private function decodeJsonResponse(string $rawResponse): ?array
    {
        $rawResponse = trim($rawResponse);
        $rawResponse = preg_replace('/^```(?:json)?\s*/i', '', $rawResponse) ?? $rawResponse;
        $rawResponse = preg_replace('/\s*```$/i', '', $rawResponse) ?? $rawResponse;

        $decoded = json_decode($rawResponse, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $rawResponse, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function validateAndFormatResponse(array $data): array
    {
        $default = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'location' => '',
            'title' => '',
            'summary' => '',
            'skills' => [],
            'experience' => [],
            'education' => [],
            'projects' => [],
            'certifications' => [],
            'languages' => [],
            'achievements' => [],
            'volunteer' => [],
            'references' => [],
        ];

        $result = array_merge($default, $data);

        foreach (['name', 'email', 'phone', 'location', 'title', 'summary'] as $field) {
            $result[$field] = is_scalar($result[$field]) ? trim((string) $result[$field]) : '';
        }

        $result['skills'] = $this->normalizeSkills($result['skills']);

        $experience = is_array($result['experience']) ? $result['experience'] : [];
        $result['experience'] = array_values(array_filter(array_map([$this, 'normalizeExperienceEntry'], $experience)));
        $result['experience'] = $this->deduplicateEntries($result['experience'], ['company', 'position', 'start_date', 'end_date']);

        $education = is_array($result['education']) ? $result['education'] : [];
        $result['education'] = array_values(array_filter(array_map([$this, 'normalizeEducationEntry'], $education)));
        $result['education'] = $this->deduplicateEntries($result['education'], ['institution', 'degree', 'year']);

        $projects = is_array($result['projects']) ? $result['projects'] : [];
        $result['projects'] = array_values(array_filter(array_map([$this, 'normalizeProjectEntry'], $projects)));
        $result['projects'] = $this->deduplicateEntries($result['projects'], ['name', 'description']);

        $languages = is_array($result['languages']) ? $result['languages'] : [];
        $result['languages'] = array_values(array_filter(array_map([$this, 'normalizeLanguageEntry'], $languages)));
        $result['languages'] = $this->deduplicateEntries($result['languages'], ['language']);

        foreach (['certifications', 'achievements', 'volunteer', 'references'] as $field) {
            $result[$field] = $this->normalizeStringList($result[$field]);
        }

        return $result;
    }

    private function ensureResponseMatchesSource(array $result, string $source, array $fallback): array
    {
        $sourceLower = mb_strtolower($source);
        $sourceCompact = preg_replace('/[^a-z0-9+@.]+/i', '', $source) ?? '';

        // Critical identity fields are source-of-truth. A local model can easily
        // hallucinate/alter these, especially when the PDF parser contains OCR noise.
        foreach (['name', 'email', 'phone', 'location', 'title'] as $critical) {
            $fallbackValue = trim((string) ($fallback[$critical] ?? ''));
            if ($fallbackValue !== '') {
                $result[$critical] = $fallbackValue;
            }
        }

        // Contact details are never allowed to be invented.
        if (! $this->valueExistsInSource($result['email'], $sourceLower)) {
            $result['email'] = $fallback['email'] ?? '';
        }

        if (! $this->phoneExistsInSource($result['phone'], $sourceCompact)) {
            $result['phone'] = $fallback['phone'] ?? '';
        }

        if (! $this->nameExistsInSource($result['name'], $sourceLower)) {
            $result['name'] = $fallback['name'] ?? '';
        }

        // Summary is the only field where light rewriting is useful, but it must
        // remain grounded in the source. If the model returns a generic/invented
        // summary, use the actual Profile/Summary section extracted from the CV.
        $sourceSummary = trim((string) ($fallback['summary'] ?? ''));
        if ($sourceSummary !== '') {
            $resultSummary = trim((string) ($result['summary'] ?? ''));
            $generic = preg_match('/^(professional|experienced)\b.*\b(experience|responsibilities|quality work)\.?$/i', $resultSummary);
            if ($resultSummary === '' || $generic || ! $this->summaryIsGrounded($resultSummary, $sourceLower)) {
                $result['summary'] = $sourceSummary;
            }
        }

        // Skills are critical for the editor. If Ollama omitted them or returned
        // unsupported skills, use the deterministic source extraction instead.
        $sourceSkills = $fallback['skills'] ?? [];
        $resultSkills = $result['skills'] ?? [];

        $supportedSkills = [];
        foreach ($resultSkills as $skill) {
            if ($this->valueExistsInSource($skill, $sourceLower)) {
                $supportedSkills[] = $skill;
            }
        }

        // The source parser is intentionally authoritative for skills because
        // CV skill lists are usually comma-separated and easy to extract exactly.
        if (! empty($sourceSkills)) {
            $result['skills'] = $sourceSkills;
        } else {
            $result['skills'] = array_values(array_unique($supportedSkills));
        }

        // If Ollama omitted a location, keep the source-derived location.
        if ($result['location'] === '') {
            $result['location'] = $fallback['location'] ?? '';
        }

        // Do not allow the model to invent experience/education/projects when the
        // source has no corresponding section. For populated sections we retain
        // the model's cleaned descriptions but remove entries with no source anchor.
        if (empty($fallback['experience'])) {
            $result['experience'] = [];
        } else {
            $result['experience'] = $this->filterEntriesBySource($result['experience'], $sourceLower, ['company', 'position']);
            if (empty($result['experience'])) {
                $result['experience'] = $fallback['experience'];
            }
        }

        if (empty($fallback['education'])) {
            $result['education'] = [];
        } else {
            $result['education'] = $this->filterEntriesBySource($result['education'], $sourceLower, ['institution', 'degree']);
            if (empty($result['education'])) {
                $result['education'] = $fallback['education'];
            }
        }

        if (empty($fallback['projects'])) {
            $result['projects'] = [];
        } else {
            $result['projects'] = $this->filterEntriesBySource($result['projects'], $sourceLower, ['name']);
            if (empty($result['projects'])) {
                $result['projects'] = $fallback['projects'];
            }
        }

        return $result;
    }

    private function summaryIsGrounded(string $summary, string $sourceLower): bool
    {
        $words = preg_split('/\s+/', mb_strtolower($summary), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $meaningful = [];
        foreach ($words as $word) {
            $word = preg_replace('/[^a-z0-9]+/i', '', $word) ?? '';
            if (mb_strlen($word) >= 5) $meaningful[] = $word;
        }
        if (count($meaningful) < 3) return true;
        $matches = 0;
        foreach (array_unique($meaningful) as $word) {
            if (str_contains($sourceLower, $word)) $matches++;
        }
        return ($matches / max(1, count(array_unique($meaningful)))) >= 0.35;
    }

    private function filterEntriesBySource(array $entries, string $sourceLower, array $anchorKeys): array
    {
        $filtered = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $matched = false;
            foreach ($anchorKeys as $key) {
                $value = trim((string) ($entry[$key] ?? ''));
                if ($value !== '' && $this->valueExistsInSource($value, $sourceLower)) {
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                $filtered[] = $entry;
            }
        }

        return $filtered;
    }

    private function valueExistsInSource(string $value, string $sourceLower): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        $needle = mb_strtolower($value);
        if (mb_strlen($needle) < 3) {
            return false;
        }

        return str_contains($sourceLower, $needle);
    }

    private function phoneExistsInSource(string $phone, string $sourceCompact): bool
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return false;
        }

        return str_contains($sourceCompact, $digits);
    }

    private function nameExistsInSource(string $name, string $sourceLower): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        $normalized = preg_replace('/\s+/', ' ', mb_strtolower($name)) ?? '';
        return str_contains($sourceLower, $normalized)
            || str_contains($sourceLower, str_replace(' ', '', $normalized));
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
            $skill = preg_replace('/\s+/', ' ', $skill) ?? $skill;
            $skill = trim($skill, " \t\n\r\0\x0B,;|");

            if ($skill === '') {
                continue;
            }

            // Preserve the user's technology casing instead of turning
            // JavaScript -> Javascript or TypeScript -> Typescript.
            $skill = $this->normalizeAcronyms($skill);
            $cleanSkills[] = $skill;
        }

        return array_values(array_unique($cleanSkills));
    }

    private function normalizeAcronyms(string $text): string
    {
        $replacements = [
            'php' => 'PHP', 'mysql' => 'MySQL', 'sql' => 'SQL', 'html' => 'HTML',
            'html5' => 'HTML5', 'css' => 'CSS', 'css3' => 'CSS3', 'api' => 'API',
            'crm' => 'CRM', 'erp' => 'ERP', 'lms' => 'LMS', 'cms' => 'CMS',
            'ui' => 'UI', 'ux' => 'UX', 'ai' => 'AI', 'seo' => 'SEO', 'laravel' => 'Laravel',
            'javascript' => 'JavaScript', 'typescript' => 'TypeScript', 'wordpress' => 'WordPress',
            'tailwind css' => 'Tailwind CSS', 'bootstrap' => 'Bootstrap', 'ionic' => 'Ionic',
        ];

        foreach ($replacements as $from => $to) {
            $text = preg_replace('/(?<![A-Za-z0-9])' . preg_quote($from, '/') . '(?![A-Za-z0-9])/i', $to, $text) ?? $text;
        }

        return $text;
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
        $grade = trim((string) ($entry['grade'] ?? $entry['cgpa'] ?? ''));

        if ($institution === '' && $degree === '' && $year === '' && $grade === '') {
            return [];
        }

        return [
            'institution' => $institution,
            'degree' => $degree,
            'year' => $this->normalizeYear($year),
            'grade' => $grade,
        ];
    }

    private function normalizeProjectEntry($entry): array
    {
        if (is_string($entry)) {
            return ['name' => '', 'description' => trim($entry), 'technologies' => []];
        }

        if (! is_array($entry)) {
            return [];
        }

        $name = trim((string) ($entry['name'] ?? $entry['title'] ?? ''));
        $description = trim((string) ($entry['description'] ?? $entry['summary'] ?? ''));
        $technologies = $this->normalizeSkills($entry['technologies'] ?? $entry['tech'] ?? []);

        if ($name === '' && $description === '') {
            return [];
        }

        return [
            'name' => $name,
            'description' => $description,
            'technologies' => $technologies,
        ];
    }

    private function normalizeLanguageEntry($entry): array
    {
        if (is_string($entry)) {
            $entry = ['language' => $entry, 'proficiency' => ''];
        }

        if (! is_array($entry)) {
            return [];
        }

        $language = trim((string) ($entry['language'] ?? ''));
        $proficiency = trim((string) ($entry['proficiency'] ?? ''));

        if ($language === '') {
            return [];
        }

        return ['language' => $language, 'proficiency' => $proficiency];
    }

    private function normalizeStringList($value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\r\n•]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (! is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $item = $item['name'] ?? $item['title'] ?? $item['description'] ?? '';
            }
            $item = trim((string) $item);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }

    private function normalizeYear(string $year): string
    {
        $year = trim(preg_replace('/\s+/', ' ', $year) ?? $year);
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
        $date = trim(preg_replace('/\s+/', ' ', str_replace(['–', '—', '/'], '-', $date)) ?? $date);
        if ($date === '') {
            return '';
        }

        if (preg_match('/^(present|current)$/i', $date)) {
            return 'Present';
        }

        if (preg_match('/^(.*?)\s*(?:-|to)\s*(present|current)$/i', $date, $matches)) {
            $start = $this->normalizeDate(trim($matches[1]));
            return ($start !== '' ? $start . ' - ' : '') . 'Present';
        }

        if (preg_match('/^(\d{4})\s*-\s*(\d{1,2})$/', $date, $matches)) {
            $month = (int) $matches[2];
            if ($month >= 1 && $month <= 12) {
                $parsedMonth = \DateTime::createFromFormat('n Y', "{$month} {$matches[1]}");
                if ($parsedMonth !== false) {
                    return $parsedMonth->format('F Y');
                }
            }
        }

        if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $date, $matches)) {
            return "{$matches[1]} - {$matches[2]}";
        }

        foreach (['F Y', 'M Y', 'Y-m', 'Y-n', 'm-Y', 'n-Y', 'Y'] as $format) {
            $parsed = \DateTime::createFromFormat($format, $date);
            if ($parsed !== false) {
                return $format === 'Y' ? $parsed->format('Y') : $parsed->format('F Y');
            }
        }

        return preg_replace('/\s*-\s*/', ' - ', $date) ?? $date;
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
                $hashParts[] = mb_strtolower(trim((string) ($entry[$key] ?? '')));
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

    /**
     * Deterministic fallback parser. It is intentionally source-based and is
     * also used as an authority for skills/contact fields after Ollama returns.
     */
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

        $phone = preg_match('/(?:\+?\d[\d\s().\-]{7,}\d)/', $plainText, $phoneMatch)
            ? trim($phoneMatch[0])
            : '';

        $name = $this->extractName($lines, $email, $phone);
        $location = $this->extractLocation($lines, $name, $email, $phone);
        $title = $this->extractTitle($lines);
        $summary = $this->sectionText($lines, ['summary', 'profile', 'professional summary', 'about me']);
        $skills = $this->extractSkills($lines);
        $education = $this->extractEducation($lines);
        $experience = $this->extractExperience($lines);
        $projects = $this->extractProjects($lines);
        $languages = $this->extractHumanLanguages($lines);

        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'location' => $location,
            'title' => $title,
            'summary' => $summary,
            'skills' => $skills,
            'experience' => $experience,
            'education' => $education,
            'projects' => $projects,
            'certifications' => $this->extractListSection($lines, ['certifications', 'certificates']),
            'languages' => $languages,
            'achievements' => $this->extractListSection($lines, ['achievements', 'awards', 'honors']),
            'volunteer' => $this->extractListSection($lines, ['volunteer', 'volunteering']),
            'references' => $this->extractListSection($lines, ['references']),
        ];
    }

    private function extractName(array $lines, string $email, string $phone): string
    {
        foreach (array_slice($lines, 0, 8) as $line) {
            $clean = trim($line);
            if ($clean === '' || str_contains($clean, '@') || preg_match('/\d{5,}/', $clean)) {
                continue;
            }
            if (in_array(strtolower(rtrim($clean, ':')), ['cv', 'resume', 'curriculum vitae'], true)) {
                continue;
            }
            if (preg_match('/^(skills|education|profile|summary|experience|work experience|projects?)$/i', $clean)) {
                continue;
            }
            if (preg_match('/^[A-Za-z][A-Za-z .\'-]{2,80}$/', $clean)) {
                return $clean;
            }
        }

        return '';
    }

    private function extractLocation(array $lines, string $name, string $email, string $phone): string
    {
        foreach (array_slice($lines, 0, 12) as $line) {
            $clean = trim($line);
            if ($clean === '' || $clean === $name || str_contains($clean, '@') || $clean === $phone) {
                continue;
            }
            if (preg_match('/\b(Pakistan|India|UAE|United Arab Emirates|UK|United Kingdom|USA|United States|Canada|Australia)\b/i', $clean)) {
                return $clean;
            }
        }

        return '';
    }

    private function extractTitle(array $lines): string
    {
        // Prefer a clear job title inside the Profile/Summary section.
        $profile = $this->sectionLines($lines, ['profile', 'summary', 'professional summary', 'about me']);
        foreach ($profile as $line) {
            if (preg_match('/\b(full\s*stack\s*(developer|engineer)|backend\s*developer|software\s*engineer|frontend\s*developer|web\s*developer|mobile\s*developer)\b/i', $line, $m)) {
                return trim($m[0]);
            }
        }

        $blocked = [
            'skills', 'education', 'profile', 'summary', 'experience', 'work experience',
            'projects', 'key projects', 'certifications', 'languages', 'references',
        ];

        foreach (array_slice($lines, 0, 12) as $line) {
            $clean = trim($line);
            if ($clean === '' || in_array(strtolower(rtrim($clean, ':')), $blocked, true)) {
                continue;
            }
            if (str_contains($clean, '@') || preg_match('/\d{5,}/', $clean)) {
                continue;
            }
            if (preg_match('/developer|engineer|designer|manager|analyst|specialist|consultant|administrator|director|architect|student|intern|full\s*stack/i', $clean)) {
                return $clean;
            }
        }

        return '';
    }

    private function extractSkills(array $lines): array
    {
        $headings = [
            'skills', 'technical skills', 'core skills', 'technical expertise', 'technologies',
            'skills & technologies', 'skills and technologies',
        ];

        $stop = $this->sectionHeadings();
        $skills = [];
        $inSkills = false;

        foreach ($lines as $line) {
            $normalized = strtolower(rtrim(trim($line), ':'));

            if (in_array($normalized, $headings, true)) {
                $inSkills = true;
                continue;
            }

            if ($inSkills && in_array($normalized, $stop, true)) {
                break;
            }

            if (! $inSkills) {
                continue;
            }

            // Category headings are labels, not skills.
            if (preg_match('/^(languages?\s*&\s*frameworks|web technologies|databases?|database|other|tools?|platforms?)$/i', $line)) {
                continue;
            }

            $parts = preg_split('/[,;|]+/', trim(ltrim($line, "-*• \\t")), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $skills[] = $part;
                }
            }
        }

        // Some CVs use a single heading followed by several category blocks.
        return $this->normalizeSkills($skills);
    }

    private function extractEducation(array $lines): array
    {
        $blocks = $this->sectionLines($lines, ['education', 'academic background']);
        if (empty($blocks)) {
            return [];
        }

        $entries = [];
        $current = [];

        foreach ($blocks as $line) {
            if (preg_match('/\b(19|20)\d{2}\s*[-–—]\s*(19|20)\d{2}\b/', $line, $m)) {
                $current['year'] = str_replace(['–', '—'], '-', $m[0]);
                continue;
            }
            if (preg_match('/\b(19|20)\d{2}\b/', $line, $m) && empty($current['year'])) {
                $current['year'] = $m[0];
                continue;
            }

            if (preg_match('/\b(BS|MS|MBA|MA|BA|BSc|MSc|PhD|Bachelor|Master|Diploma|Associate|Intermediate|Matric)\b/i', $line)) {
                $current['degree'] = $line;
                continue;
            }

            if (! isset($current['institution']) && preg_match('/\b(University|College|Institute|School|GIMS|PMAS|Academy)\b/i', $line)) {
                $current['institution'] = $line;
                continue;
            }

            if (count($current) > 0 && ! isset($current['institution'])) {
                $current['institution'] = $line;
            }
        }

        if (! empty($current)) {
            $entries[] = $current;
        }

        return array_values(array_filter(array_map([$this, 'normalizeEducationEntry'], $entries)));
    }

    private function extractExperience(array $lines): array
    {
        $blocks = $this->sectionLines($lines, ['experience', 'work experience', 'professional experience', 'employment history']);
        if (empty($blocks)) {
            return [];
        }

        $entries = [];
        $current = [];
        $description = [];

        foreach ($blocks as $line) {
            if (preg_match('/^(19|20)\d{2}\s*[-–—]\s*(present|(19|20)\d{2})$/i', $line, $m)) {
                $current['start_date'] = trim(explode('-', str_replace(['–', '—'], '-', $line))[0]);
                $current['end_date'] = trim(explode('-', str_replace(['–', '—'], '-', $line))[1] ?? '');
                continue;
            }

            if (preg_match('/^(.+?)\s*\|\s*(.+?)\s*$/', $line, $m) && empty($current['company'])) {
                $current['position'] = trim($m[1]);
                $current['company'] = trim($m[2]);
                continue;
            }

            if (empty($current['position']) && preg_match('/developer|engineer|manager|designer|analyst|consultant|architect|administrator|specialist/i', $line)) {
                $current['position'] = $line;
                continue;
            }

            if (empty($current['company']) && preg_match('/\b(Technologies|Technology|Inc\.?|Ltd\.?|LLC|Solutions|Systems|Company|University|Institute)\b/i', $line)) {
                $current['company'] = $line;
                continue;
            }

            if ($line !== '') {
                $description[] = ltrim($line, "-*• \\t");
            }
        }

        if (! empty($current) || ! empty($description)) {
            $current['description'] = trim(implode(' ', $description));
            $entries[] = $current;
        }

        return array_values(array_filter(array_map([$this, 'normalizeExperienceEntry'], $entries)));
    }

    private function extractProjects(array $lines): array
    {
        $blocks = $this->sectionLines($lines, ['projects', 'key projects', 'selected projects']);
        if (empty($blocks)) {
            return [];
        }

        $projects = [];
        $currentName = '';
        $description = [];

        foreach ($blocks as $line) {
            if (preg_match('/^([^—–-]+?)\s*[—–-]\s*(.+)$/', $line, $m)) {
                if ($currentName !== '') {
                    $projects[] = [
                        'name' => $currentName,
                        'description' => trim(implode(' ', $description)),
                        'technologies' => [],
                    ];
                }
                $currentName = trim($m[1]);
                $description = [trim($m[2])];
            } elseif ($currentName === '') {
                $currentName = trim($line);
            } else {
                $description[] = trim($line);
            }
        }

        if ($currentName !== '') {
            $projects[] = [
                'name' => $currentName,
                'description' => trim(implode(' ', $description)),
                'technologies' => [],
            ];
        }

        return array_values(array_filter(array_map([$this, 'normalizeProjectEntry'], $projects)));
    }

    private function extractHumanLanguages(array $lines): array
    {
        $blocks = $this->sectionLines($lines, ['languages', 'spoken languages', 'languages spoken']);
        $result = [];

        foreach ($blocks as $line) {
            $parts = preg_split('/[,;|]+/', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $result[] = ['language' => $part, 'proficiency' => ''];
                }
            }
        }

        return array_values(array_filter(array_map([$this, 'normalizeLanguageEntry'], $result)));
    }

    private function extractListSection(array $lines, array $headings): array
    {
        $blocks = $this->sectionLines($lines, $headings);
        return $this->normalizeStringList($blocks);
    }

    private function sectionText(array $lines, array $headings): string
    {
        return implode(' ', $this->sectionLines($lines, $headings));
    }

    private function sectionLines(array $lines, array $headings): array
    {
        $headings = array_map(static fn($v) => strtolower(rtrim($v, ':')), $headings);
        $stop = $this->sectionHeadings();

        foreach ($lines as $index => $line) {
            $normalized = strtolower(rtrim(trim($line), ':'));
            if (! in_array($normalized, $headings, true)) {
                continue;
            }

            $content = [];
            for ($next = $index + 1; isset($lines[$next]); $next++) {
                $nextNormalized = strtolower(rtrim(trim($lines[$next]), ':'));
                if (in_array($nextNormalized, $stop, true)) {
                    break;
                }
                $content[] = trim($lines[$next]);
            }

            return $content;
        }

        return [];
    }

    private function sectionHeadings(): array
    {
        return [
            'summary', 'profile', 'professional summary', 'about me',
            'skills', 'technical skills', 'core skills', 'technical expertise', 'technologies',
            'experience', 'work experience', 'professional experience', 'employment history',
            'education', 'academic background',
            'projects', 'key projects', 'selected projects',
            'certifications', 'certificates', 'languages', 'spoken languages', 'languages spoken',
            'achievements', 'awards', 'honors', 'volunteer', 'volunteering', 'references',
        ];
    }

    public function improveDescription(string $text, string $type = 'experience'): string
    {
        if (! config('services.ollama.enabled', false)) {
            return trim($text);
        }

        $prompt = $this->buildImprovementPrompt($text, $type);

        try {
            $response = Http::timeout(120)->post($this->apiUrl, [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => ['temperature' => 0.2],
            ]);

            if ($response->successful()) {
                return trim((string) data_get($response->json(), 'response', $text));
            }

            return $text;
        } catch (\Throwable $e) {
            Log::error('AI Improvement Error: ' . $e->getMessage());
            return $text;
        }
    }

    private function buildImprovementPrompt(string $text, string $type): string
    {
        $typeMap = [
            'experience' => 'job description with achievements and metrics',
            'summary' => 'professional summary',
            'skill' => 'skill name',
        ];

        $typeLabel = $typeMap[$type] ?? 'text';

        return <<<EOT
Improve the following {$typeLabel} using ONLY the facts already present in the supplied text.
Do not invent companies, dates, technologies, metrics, responsibilities, or achievements.
Return only the improved text.

Original:
{$text}
EOT;
    }
}
