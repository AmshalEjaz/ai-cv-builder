<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Modern Teal',
                'slug' => 'modern-teal',
                'thumbnail' => 'images/templates/modern.png',
                'description' => 'A clean, confident layout for technology, product, and creative roles.',
                'settings' => ['accent' => '#167a74', 'style' => 'modern'],
            ],
            [
                'name' => 'Executive Slate',
                'slug' => 'executive-slate',
                'thumbnail' => 'images/templates/executive-slate.png',
                'description' => 'A structured and polished design for leadership and business professionals.',
                'settings' => ['accent' => '#263746', 'style' => 'executive'],
            ],
            [
                'name' => 'Creative Coral',
                'slug' => 'creative-coral',
                'thumbnail' => 'images/templates/gray and golden resume cv.png',
                'description' => 'A bold, expressive template that helps creative experience stand out.',
                'settings' => ['accent' => '#d46c5d', 'style' => 'creative'],
            ],
            [
                'name' => 'Minimal Stone',
                'slug' => 'minimal-stone',
                'thumbnail' => 'images/templates/modern-tale.png',
                'description' => 'A calm, minimal layout that keeps the focus on your achievements.',
                'settings' => ['accent' => '#7b756d', 'style' => 'minimal'],
            ],
        ];

        foreach ($templates as $template) {
            Template::updateOrCreate(
                ['slug' => $template['slug']],
                [...$template, 'is_active' => true],
            );
        }
    }
}
