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
        $ai = is_array($this->ai_enhanced_data) ? $this->ai_enhanced_data : [];
        $parsed = is_array($this->parsed_data) ? $this->parsed_data : [];
        $raw = (string) ($parsed['raw_text'] ?? '');

        // Never let null/empty AI fields erase information from the uploaded CV.
        // The AI result is preferred only when it actually contains a value.
        $skills = $ai['skills'] ?? [];
        if (!is_array($skills) || count($skills) === 0) {
            $skills = $this->extractSkills($raw);
        }

        $experience = $ai['experience'] ?? [];
        if (!is_array($experience) || count($experience) === 0) {
            $experience = $this->extractExperience($raw);
        }

        $education = $ai['education'] ?? [];
        if (!is_array($education) || count($education) === 0) {
            $education = $this->extractEducation($raw);
        }

        return [
            'name' => $this->nonEmpty($ai['name'] ?? null, $this->extractName($raw), $this->title),
            'title' => $this->nonEmpty($ai['title'] ?? null, $this->extractJobTitle($raw), ''),
            'email' => $this->nonEmpty($ai['email'] ?? null, $this->extractEmail($raw), ''),
            'phone' => $this->nonEmpty($ai['phone'] ?? null, $this->extractPhone($raw), ''),
            'location' => $this->nonEmpty($ai['location'] ?? null, $this->extractLocation($raw), ''),
            'summary' => $this->nonEmpty($ai['summary'] ?? null, $this->extractSummary($raw), ''),
            'skills' => array_values(array_unique(array_filter($skills, fn($v) => is_scalar($v) && trim((string)$v) !== ''))),
            'experience' => is_array($experience) ? $experience : [],
            'education' => is_array($education) ? $education : [],
            'projects' => is_array($ai['projects'] ?? null) ? $ai['projects'] : $this->extractProjects($raw),
            'certifications' => is_array($ai['certifications'] ?? null) ? $ai['certifications'] : [],
            'languages' => is_array($ai['languages'] ?? null) ? $ai['languages'] : [],
            'achievements' => is_array($ai['achievements'] ?? null) ? $ai['achievements'] : [],
            'volunteer' => is_array($ai['volunteer'] ?? null) ? $ai['volunteer'] : [],
            'references' => is_array($ai['references'] ?? null) ? $ai['references'] : [],
        ];
    }

    private function nonEmpty($primary, ?string $fallback, string $default = ''): string
    {
        $primary = is_scalar($primary) ? trim((string)$primary) : '';
        if ($primary !== '') return $primary;
        return $fallback !== null && trim($fallback) !== '' ? trim($fallback) : $default;
    }

    private function extractSkills(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
        $skills = [];
        $in = false;
        foreach ($lines as $line) {
            $h = strtolower(rtrim($line, ':'));
            if ($h === 'skills' || $h === 'technical skills' || $h === 'core skills') { $in = true; continue; }
            if ($in && in_array($h, ['education','profile','summary','experience','work experience','projects','key projects','certifications','languages','references'], true)) break;
            if (!$in || preg_match('/^(languages? & frameworks|web technologies|database|databases|other|tools?|platforms?)$/i', $line)) continue;
            foreach (preg_split('/[,;|]+/', ltrim($line, "-*• \t"), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $skill) {
                $skill=trim($skill);
                if ($skill !== '') $skills[]=$skill;
            }
        }
        return array_values(array_unique($skills));
    }

    private function extractLocation(string $text): ?string
    {
        foreach (array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: []))) as $line) {
            if (preg_match('/\b(Pakistan|India|UAE|United Arab Emirates|UK|United Kingdom|USA|United States|Canada|Australia)\b/i', $line)) {
                if (!str_contains($line, '@') && !preg_match('/\d{7,}/', $line)) return $line;
            }
        }
        return null;
    }

    private function extractExperience(string $text): array
    {
        // Keep this conservative: if AI already supplied experience, this is only
        // a fallback. It extracts obvious job blocks and stops at Projects/Education.
        $lines=array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
        $start=-1; $end=count($lines);
        foreach($lines as $i=>$line){
            $h=strtolower(rtrim($line, ':'));
            if($start<0 && in_array($h,['experience','work experience','professional experience','employment history'],true)){$start=$i+1;continue;}
            if($start>=0 && in_array($h,['education','projects','key projects','selected projects','certifications','languages','references'],true)){ $end=$i; break; }
        }
        if($start<0) return [];
        $blocks=[]; $current=null; $desc=[];
        $flush=function() use (&$blocks,&$current,&$desc){
            if(is_array($current) && (($current['company']??'')!=='' || ($current['position']??'')!=='')){
                $current['description']=trim(implode(' ', $desc)); $blocks[]=$current;
            }
            $current=null; $desc=[];
        };
        for($i=$start;$i<$end;$i++){
            $line=$lines[$i];
            if(preg_match('/^(developer|engineer|designer|manager|analyst|consultant|architect|administrator|specialist|intern)(?:\s*\/\s*.+)?$/i',$line)){
                if($current && !empty($current['position'])) $flush();
                $current=['position'=>$line,'company'=>'','start_date'=>'','end_date'=>'']; continue;
            }
            if($current && ($current['company']??'')==='' && preg_match('/^(.+?)(?:\s*[·|]\s*.*)?$/u',$line,$m) && !preg_match('/^(building|developed|developing|architected|engineered|collaborating|built|designed|created|responsible)\b/i',$line)){
                $current['company']=trim($m[1]); continue;
            }
            if($current) $desc[]=ltrim($line,'-*• \t');
        }
        $flush();
        return $blocks;
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

