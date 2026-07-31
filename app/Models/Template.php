<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Template extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'thumbnail', 'description', 'settings', 'is_active'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function cvs()
    {
        return $this->hasMany(CV::class);
    }
}
