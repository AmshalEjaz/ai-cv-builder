<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'thumbnail', 'pdf_path', 'description', 'settings', 'is_active', 'use_pdf_background'];

    protected $casts = [
        'settings' => 'array',
        'use_pdf_background' => 'boolean',
    ];

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail) {
            return null;
        }

        return asset(ltrim($this->thumbnail, '/'));
    }

    public function cvs(): HasMany
    {
        return $this->hasMany(CV::class);
    }
}
