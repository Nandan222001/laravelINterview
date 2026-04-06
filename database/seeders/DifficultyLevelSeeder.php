<?php

namespace Database\Seeders;

use App\Models\DifficultyLevel;
use Illuminate\Database\Seeder;

class DifficultyLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Beginner',
                'slug' => 'beginner',
                'description' => 'For those just starting out',
                'level' => 1,
                'color' => '#10B981',
                'metadata' => ['icon' => 'star', 'badge_color' => 'green'],
            ],
            [
                'name' => 'Easy',
                'slug' => 'easy',
                'description' => 'Basic concepts and simple problems',
                'level' => 2,
                'color' => '#3B82F6',
                'metadata' => ['icon' => 'star', 'badge_color' => 'blue'],
            ],
            [
                'name' => 'Intermediate',
                'slug' => 'intermediate',
                'description' => 'Moderate difficulty requiring solid understanding',
                'level' => 3,
                'color' => '#F59E0B',
                'metadata' => ['icon' => 'trophy', 'badge_color' => 'yellow'],
            ],
            [
                'name' => 'Advanced',
                'slug' => 'advanced',
                'description' => 'Challenging problems for experienced developers',
                'level' => 4,
                'color' => '#EF4444',
                'metadata' => ['icon' => 'medal', 'badge_color' => 'red'],
            ],
            [
                'name' => 'Expert',
                'slug' => 'expert',
                'description' => 'Expert level problems requiring deep knowledge',
                'level' => 5,
                'color' => '#8B5CF6',
                'metadata' => ['icon' => 'medal', 'badge_color' => 'purple'],
            ],
        ];

        foreach ($levels as $level) {
            DifficultyLevel::create($level);
        }
    }
}
