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
                'name' => 'Executive Slate',
                'slug' => 'executive-slate',
                'description' => 'Modern and clean design for executives',
                'thumbnail' => 'images/templates/executive-slate.png',
                'settings' => json_encode(['accent' => '#1a365d', 'font' => 'Inter']),
                'is_active' => true,
            ],
            [
                'name' => 'Minimalist Pro',
                'slug' => 'minimalist-pro',
                'description' => 'Simple and elegant minimalist design',
                'thumbnail' => 'images/templates/minimalist-pro.png',
                'settings' => json_encode(['accent' => '#2563eb', 'font' => 'Inter']),
                'is_active' => true,
            ],
            [
                'name' => 'Creative Bold',
                'slug' => 'creative-bold',
                'description' => 'Bold design for creative professionals',
                'thumbnail' => 'images/templates/creative-bold.png',
                'settings' => json_encode(['accent' => '#7c3aed', 'font' => 'Poppins']),
                'is_active' => true,
            ],
            [
                'name' => 'Classic Elegant',
                'slug' => 'classic-elegant',
                'description' => 'Traditional elegant design',
                'thumbnail' => 'images/templates/classic-elegant.png',
                'settings' => json_encode(['accent' => '#0f172a', 'font' => 'Georgia']),
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            // Update or Create - prevents duplicates
            Template::updateOrCreate(
                ['slug' => $template['slug']], // Find by slug
                $template // Update with these values
            );
        }

        $this->command->info('✅ Templates seeded successfully!');
    }
}