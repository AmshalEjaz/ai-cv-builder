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
                'description' => 'Teal banner header with a clean two-column body.',
                'thumbnail' => 'images/templates/modern-teal.png',
                'settings' => ['accent' => '#167a74', 'layout' => 'modern-teal'],
                'is_active' => true,
            ],
            [
                'name' => 'Executive Slate',
                'slug' => 'executive-slate',
                'description' => 'Light header bar with a dark professional sidebar.',
                'thumbnail' => 'images/templates/executive-slate.png',
                'settings' => ['accent' => '#2c2c2c', 'layout' => 'executive-slate'],
                'is_active' => true,
            ],
            [
                'name' => 'Gray & Golden',
                'slug' => 'gray-and-golden-resume-cv',
                'description' => 'Dark sidebar with an initials avatar and a timeline of experience.',
                'thumbnail' => 'images/templates/gray_and_golden_resume_cv.png',
                'settings' => ['accent' => '#cda45e', 'layout' => 'gray-golden'],
                'is_active' => true,
            ],
            [
                'name' => 'Black & White Simple',
                'slug' => 'black-and-white-simple-cv-resume',
                'description' => 'Bordered centered name with a clean split-column body.',
                'thumbnail' => 'images/templates/Black_and_White_Simple_CV_Resume.png',
                'settings' => ['accent' => '#f0ad4e', 'layout' => 'centered-classic'],
                'is_active' => true,
            ],
            [
                'name' => 'Art Director',
                'slug' => 'modern',
                'description' => 'Bold dark banner with a two-tone name and timeline body.',
                'thumbnail' => 'images/templates/modern.png',
                'settings' => ['accent' => '#e0b23c', 'layout' => 'art-director'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            Template::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('✅ Templates seeded successfully!');
    }
}
