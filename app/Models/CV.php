<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CV extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'title',
        'original_filename',
        'file_path',
        'parsed_data',
        'ai_enhanced_data',
        'template_id',
        'status'
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'ai_enhanced_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Merge ai_enhanced_data with fallbacks from parsed_data.raw_text so the
     * generated PDF always contains the user's original information when AI
     * didn't supply a specific field.
     *
     * @return array
     */
    public function getRenderedDataAttribute(): array
    {
        $ai = $this->ai_enhanced_data ?? [];
        $parsed = $this->parsed_data ?? [];
        $raw = isset($parsed['raw_text']) ? $parsed['raw_text'] : '';

        $result = [
            'name' => $ai['name'] ?? $this->extractName($raw) ?? $this->title,
            'title' => $ai['title'] ?? $this->extractJobTitle($raw) ?? ($ai['title'] ?? ''),
            'email' => $ai['email'] ?? $this->extractEmail($raw),
            'phone' => $ai['phone'] ?? $this->extractPhone($raw),
            'summary' => $ai['summary'] ?? $this->extractSummary($raw),
            'skills' => $ai['skills'] ?? [],
            'experience' => $ai['experience'] ?? [],
            'education' => $ai['education'] ?? [],
        ];

        return $result;
    }

    private function extractEmail(string $text): ?string
    {
        if (preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,6}/', $text, $m)) {
            return $m[0];
        }
        return null;
    }

    private function extractPhone(string $text): ?string
    {
        // basic phone number regex (international/common formats)
        if (preg_match('/(\+?\d[\d\s().-]{6,}\d)/', $text, $m)) {
            return trim($m[0]);
        }
        return null;
    }

    private function extractName(string $text): ?string
    {
        // heuristic: pick the first non-empty line that looks like a name (letters and spaces, 2-4 words)
        $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $text)));
        foreach ($lines as $line) {
            if (strlen($line) < 100 && preg_match('/^[A-Za-z .\'-]{2,80}$/', $line) && str_word_count($line) <= 4 && str_word_count($line) >= 1) {
                // skip lines that contain email or phone
                if (preg_match('/@|\d{2,}/', $line)) continue;
                return $line;
            }
        }
        return null;
    }

    private function extractJobTitle(string $text): ?string
    {
        // simple heuristic: look for a short line after the name containing 'Engineer','Manager','Developer','Designer', etc.
        if (preg_match('/\b(Engineer|Developer|Manager|Designer|Consultant|Analyst|Director)\b/i', $text, $m)) {
            return $m[0];
        }
        return null;
    }

    private function extractSummary(string $text): ?string
    {
        // return first 400 chars of raw text as a fallback summary
        $clean = preg_replace('/\s+/', ' ', trim($text));
        if ($clean === '') return null;
        return mb_substr($clean, 0, 400);
    }
}

