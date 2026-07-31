<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
