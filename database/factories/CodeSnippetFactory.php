<?php

namespace Database\Factories;

use App\Models\CodeSnippet;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodeSnippetFactory extends Factory
{
    protected $model = CodeSnippet::class;

    public function definition(): array
    {
        $language = fake()->randomElement(['php', 'javascript', 'python', 'java', 'csharp', 'ruby']);

        return [
            'question_id' => Question::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'code' => $this->generateCodeSample($language),
            'language' => $language,
            'type' => fake()->randomElement(['example', 'solution', 'test', 'template']),
            'order' => fake()->numberBetween(0, 10),
            'is_executable' => fake()->boolean(50),
            'expected_output' => fake()->optional()->sentence(),
            'metadata' => [
                'version' => '1.0',
                'framework' => fake()->optional()->word(),
            ],
        ];
    }

    public function forQuestion(int $questionId): static
    {
        return $this->state(fn (array $attributes) => [
            'question_id' => $questionId,
        ]);
    }

    public function executable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_executable' => true,
            'expected_output' => fake()->sentence(),
        ]);
    }

    private function generateCodeSample(string $language): string
    {
        return match ($language) {
            'php' => "<?php\n\nfunction example() {\n    return 'Hello, World!';\n}\n",
            'javascript' => "function example() {\n    return 'Hello, World!';\n}\n",
            'python' => "def example():\n    return 'Hello, World!'\n",
            'java' => "public String example() {\n    return \"Hello, World!\";\n}\n",
            'csharp' => "public string Example() {\n    return \"Hello, World!\";\n}\n",
            'ruby' => "def example\n  'Hello, World!'\nend\n",
            default => "// Code example\n",
        };
    }
}
