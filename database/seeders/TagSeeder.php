<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // Programming Languages
            ['name' => 'PHP', 'slug' => 'php', 'category' => 'language', 'color' => '#777BB4'],
            ['name' => 'JavaScript', 'slug' => 'javascript', 'category' => 'language', 'color' => '#F7DF1E'],
            ['name' => 'Python', 'slug' => 'python', 'category' => 'language', 'color' => '#3776AB'],
            ['name' => 'Java', 'slug' => 'java', 'category' => 'language', 'color' => '#007396'],
            ['name' => 'TypeScript', 'slug' => 'typescript', 'category' => 'language', 'color' => '#3178C6'],

            // Frameworks
            ['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'framework', 'color' => '#FF2D20'],
            ['name' => 'React', 'slug' => 'react', 'category' => 'framework', 'color' => '#61DAFB'],
            ['name' => 'Vue', 'slug' => 'vue', 'category' => 'framework', 'color' => '#4FC08D'],
            ['name' => 'Angular', 'slug' => 'angular', 'category' => 'framework', 'color' => '#DD0031'],
            ['name' => 'Node.js', 'slug' => 'nodejs', 'category' => 'framework', 'color' => '#339933'],
            ['name' => 'Express', 'slug' => 'express', 'category' => 'framework', 'color' => '#000000'],

            // Databases
            ['name' => 'MySQL', 'slug' => 'mysql', 'category' => 'database', 'color' => '#4479A1'],
            ['name' => 'PostgreSQL', 'slug' => 'postgresql', 'category' => 'database', 'color' => '#336791'],
            ['name' => 'MongoDB', 'slug' => 'mongodb', 'category' => 'database', 'color' => '#47A248'],
            ['name' => 'Redis', 'slug' => 'redis', 'category' => 'database', 'color' => '#DC382D'],
            ['name' => 'Elasticsearch', 'slug' => 'elasticsearch', 'category' => 'database', 'color' => '#005571'],

            // DevOps & Tools
            ['name' => 'Docker', 'slug' => 'docker', 'category' => 'devops', 'color' => '#2496ED'],
            ['name' => 'Kubernetes', 'slug' => 'kubernetes', 'category' => 'devops', 'color' => '#326CE5'],
            ['name' => 'Git', 'slug' => 'git', 'category' => 'tool', 'color' => '#F05032'],
            ['name' => 'GitHub', 'slug' => 'github', 'category' => 'tool', 'color' => '#181717'],
            ['name' => 'GitLab', 'slug' => 'gitlab', 'category' => 'tool', 'color' => '#FCA121'],
            ['name' => 'Jenkins', 'slug' => 'jenkins', 'category' => 'devops', 'color' => '#D24939'],
            ['name' => 'AWS', 'slug' => 'aws', 'category' => 'cloud', 'color' => '#FF9900'],
            ['name' => 'Azure', 'slug' => 'azure', 'category' => 'cloud', 'color' => '#0078D4'],
            ['name' => 'GCP', 'slug' => 'gcp', 'category' => 'cloud', 'color' => '#4285F4'],

            // Concepts
            ['name' => 'OOP', 'slug' => 'oop', 'category' => 'concept', 'color' => '#4A90E2'],
            ['name' => 'Functional Programming', 'slug' => 'functional-programming', 'category' => 'concept', 'color' => '#9013FE'],
            ['name' => 'Design Patterns', 'slug' => 'design-patterns', 'category' => 'concept', 'color' => '#50E3C2'],
            ['name' => 'Testing', 'slug' => 'testing', 'category' => 'concept', 'color' => '#7ED321'],
            ['name' => 'Security', 'slug' => 'security', 'category' => 'concept', 'color' => '#D0021B'],
            ['name' => 'Performance', 'slug' => 'performance', 'category' => 'concept', 'color' => '#F5A623'],
            ['name' => 'API', 'slug' => 'api', 'category' => 'concept', 'color' => '#BD10E0'],
            ['name' => 'REST', 'slug' => 'rest', 'category' => 'concept', 'color' => '#417505'],
            ['name' => 'GraphQL', 'slug' => 'graphql', 'category' => 'concept', 'color' => '#E10098'],

            // Data Structures & Algorithms
            ['name' => 'Algorithms', 'slug' => 'algorithms', 'category' => 'concept', 'color' => '#8B572A'],
            ['name' => 'Data Structures', 'slug' => 'data-structures', 'category' => 'concept', 'color' => '#417690'],
            ['name' => 'Sorting', 'slug' => 'sorting', 'category' => 'concept', 'color' => '#7B68EE'],
            ['name' => 'Searching', 'slug' => 'searching', 'category' => 'concept', 'color' => '#FF6347'],

            // Specific Topics
            ['name' => 'Authentication', 'slug' => 'authentication', 'category' => 'topic', 'color' => '#FF4081'],
            ['name' => 'Authorization', 'slug' => 'authorization', 'category' => 'topic', 'color' => '#E91E63'],
            ['name' => 'Caching', 'slug' => 'caching', 'category' => 'topic', 'color' => '#009688'],
            ['name' => 'Microservices', 'slug' => 'microservices', 'category' => 'topic', 'color' => '#673AB7'],
            ['name' => 'Async', 'slug' => 'async', 'category' => 'topic', 'color' => '#3F51B5'],
            ['name' => 'Concurrency', 'slug' => 'concurrency', 'category' => 'topic', 'color' => '#2196F3'],
            ['name' => 'Networking', 'slug' => 'networking', 'category' => 'topic', 'color' => '#00BCD4'],
            ['name' => 'CI/CD', 'slug' => 'cicd', 'category' => 'devops', 'color' => '#4CAF50'],

            // Additional
            ['name' => 'System Design', 'slug' => 'system-design', 'category' => 'concept', 'color' => '#795548'],
            ['name' => 'Scalability', 'slug' => 'scalability', 'category' => 'concept', 'color' => '#607D8B'],
            ['name' => 'DevOps', 'slug' => 'devops', 'category' => 'concept', 'color' => '#9E9E9E'],
        ];

        foreach ($tags as $tagData) {
            $category = $tagData['category'];
            $color = $tagData['color'];
            unset($tagData['category']);
            unset($tagData['color']);

            Tag::create(array_merge($tagData, [
                'description' => "Tag for {$tagData['name']}",
                'color' => $color,
                'usage_count' => 0,
                'metadata' => ['category' => $category, 'featured' => false],
            ]));
        }
    }
}
