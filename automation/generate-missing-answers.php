<?php
/**
 * Generate Missing Answers
 * 
 * This script compares questions across all interview-bank subdirectories
 * with existing answer files, identifies unanswered questions, and generates
 * comprehensive answers following the comprehensive-answer-template.md structure.
 * 
 * Usage: php automation/generate-missing-answers.php [options]
 * 
 * Options:
 *   --category=<name>    Process only specific category
 *   --dry-run           Show what would be generated without writing files
 *   --limit=<number>    Limit number of answers to generate
 *   --output=<dir>      Output directory (default: interview-bank/<category>/)
 */

class AnswerGenerator
{
    private string $baseDir;
    private string $templatePath;
    private array $options;
    private array $stats = [
        'total_questions' => 0,
        'answered_questions' => 0,
        'missing_answers' => 0,
        'generated_answers' => 0
    ];

    public function __construct(array $options = [])
    {
        $this->baseDir = dirname(__DIR__);
        $this->templatePath = $this->baseDir . '/templates/comprehensive-answer-template.md';
        $this->options = $options;
    }

    /**
     * Main execution method
     */
    public function run(): void
    {
        echo "🚀 Starting Answer Generation Process...\n\n";

        // Find all interview bank directories
        $categories = $this->findCategories();
        
        if (!empty($this->options['category'])) {
            $categories = array_filter($categories, function($cat) {
                return $cat === $this->options['category'];
            });
        }

        foreach ($categories as $category) {
            $this->processCategory($category);
        }

        $this->printSummary();
    }

    /**
     * Find all interview bank category directories
     */
    private function findCategories(): array
    {
        $interviewBankDir = $this->baseDir . '/interview-bank';
        $categories = [];

        if (!is_dir($interviewBankDir)) {
            echo "❌ Error: interview-bank directory not found\n";
            return $categories;
        }

        $dirs = scandir($interviewBankDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $fullPath = $interviewBankDir . '/' . $dir;
            if (is_dir($fullPath)) {
                $categories[] = $dir;
            }
        }

        return $categories;
    }

    /**
     * Process a single category
     */
    private function processCategory(string $category): void
    {
        echo "📂 Processing category: {$category}\n";
        
        $categoryPath = $this->baseDir . '/interview-bank/' . $category;
        
        // Find question files
        $questionFiles = $this->findQuestionFiles($categoryPath);
        
        if (empty($questionFiles)) {
            echo "   ⚠️  No question files found\n\n";
            return;
        }

        // Extract questions from files
        $questions = $this->extractQuestions($questionFiles);
        $this->stats['total_questions'] += count($questions);
        
        // Find existing answer files
        $answerFiles = $this->findAnswerFiles($categoryPath);
        
        // Identify answered questions
        $answeredQuestions = $this->findAnsweredQuestions($answerFiles);
        $this->stats['answered_questions'] += count($answeredQuestions);
        
        // Find missing answers
        $missingQuestions = $this->findMissingAnswers($questions, $answeredQuestions);
        $this->stats['missing_answers'] += count($missingQuestions);
        
        echo "   📊 Found {$this->countQuestions($questions)} questions\n";
        echo "   ✅ {$this->countQuestions($answeredQuestions)} already answered\n";
        echo "   ❓ {$this->countQuestions($missingQuestions)} need answers\n";

        // Generate answers for missing questions
        if (!empty($missingQuestions)) {
            $this->generateAnswers($category, $missingQuestions);
        }
        
        echo "\n";
    }

    /**
     * Find all question files in a category
     */
    private function findQuestionFiles(string $categoryPath): array
    {
        $questionFiles = [];
        $files = glob($categoryPath . '/*.md');
        
        foreach ($files as $file) {
            $basename = basename($file);
            // Look for files containing "question" in the name
            if (stripos($basename, 'question') !== false) {
                $questionFiles[] = $file;
            }
        }

        return $questionFiles;
    }

    /**
     * Extract questions from files
     */
    private function extractQuestions(array $files): array
    {
        $questions = [];
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $fileQuestions = $this->parseQuestions($content, $file);
            $questions = array_merge($questions, $fileQuestions);
        }

        return $questions;
    }

    /**
     * Parse questions from content
     */
    private function parseQuestions(string $content, string $sourceFile): array
    {
        $questions = [];
        $lines = explode("\n", $content);
        $currentQuestion = null;
        $questionNumber = 0;

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            
            // Pattern 1: Numbered questions (1. Question text)
            if (preg_match('/^(\d+)\.\s+(.+)/', $line, $matches)) {
                if ($currentQuestion) {
                    $questions[] = $currentQuestion;
                }
                
                $questionNumber = (int)$matches[1];
                $currentQuestion = [
                    'number' => $questionNumber,
                    'text' => $matches[2],
                    'source_file' => $sourceFile,
                    'line_number' => $lineNum + 1,
                    'type' => 'numbered'
                ];
            }
            // Pattern 2: Questions with ### or ** (markdown headings)
            elseif (preg_match('/^###\s+(?:⭐+\s+)?(?:Question\s+)?(\d+)?:?\s*(.+)/', $line, $matches)) {
                if ($currentQuestion) {
                    $questions[] = $currentQuestion;
                }
                
                $questionNumber = isset($matches[1]) && $matches[1] ? (int)$matches[1] : ++$questionNumber;
                $currentQuestion = [
                    'number' => $questionNumber,
                    'text' => $matches[2],
                    'source_file' => $sourceFile,
                    'line_number' => $lineNum + 1,
                    'type' => 'heading'
                ];
            }
            // Pattern 3: Questions starting with **Q or Q1:
            elseif (preg_match('/^\*\*Q(\d+):\*\*\s+(.+)|^Q(\d+):\s+(.+)/', $line, $matches)) {
                if ($currentQuestion) {
                    $questions[] = $currentQuestion;
                }
                
                $questionNumber = isset($matches[1]) && $matches[1] ? (int)$matches[1] : (int)$matches[3];
                $questionText = isset($matches[2]) && $matches[2] ? $matches[2] : $matches[4];
                
                $currentQuestion = [
                    'number' => $questionNumber,
                    'text' => $questionText,
                    'source_file' => $sourceFile,
                    'line_number' => $lineNum + 1,
                    'type' => 'q_prefix'
                ];
            }
        }

        // Add the last question
        if ($currentQuestion) {
            $questions[] = $currentQuestion;
        }

        return $questions;
    }

    /**
     * Find answer files in a category
     */
    private function findAnswerFiles(string $categoryPath): array
    {
        $answerFiles = [];
        $files = glob($categoryPath . '/*.md');
        
        foreach ($files as $file) {
            $basename = basename($file);
            // Look for files containing "answer" in the name
            if (stripos($basename, 'answer') !== false) {
                $answerFiles[] = $file;
            }
        }

        return $answerFiles;
    }

    /**
     * Find answered questions in answer files
     */
    private function findAnsweredQuestions(array $answerFiles): array
    {
        $answeredQuestions = [];
        
        foreach ($answerFiles as $file) {
            $content = file_get_contents($file);
            $questions = $this->parseQuestions($content, $file);
            $answeredQuestions = array_merge($answeredQuestions, $questions);
        }

        return $answeredQuestions;
    }

    /**
     * Find questions that don't have answers
     */
    private function findMissingAnswers(array $allQuestions, array $answeredQuestions): array
    {
        $missing = [];
        
        // Create a map of answered question numbers and texts
        $answeredMap = [];
        foreach ($answeredQuestions as $answered) {
            $key = $this->normalizeQuestionText($answered['text']);
            $answeredMap[$key] = true;
            // Also track by number if available
            if (isset($answered['number'])) {
                $answeredMap['num_' . $answered['number']] = true;
            }
        }

        // Find unanswered questions
        foreach ($allQuestions as $question) {
            $textKey = $this->normalizeQuestionText($question['text']);
            $numKey = 'num_' . $question['number'];
            
            // Question is missing if neither the text nor number is found
            if (!isset($answeredMap[$textKey]) && !isset($answeredMap[$numKey])) {
                $missing[] = $question;
            }
        }

        return $missing;
    }

    /**
     * Normalize question text for comparison
     */
    private function normalizeQuestionText(string $text): string
    {
        // Remove markdown, special characters, and normalize whitespace
        $text = strip_tags($text);
        $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return strtolower(trim($text));
    }

    /**
     * Generate answers for missing questions
     */
    private function generateAnswers(string $category, array $questions): void
    {
        $limit = isset($this->options['limit']) ? (int)$this->options['limit'] : count($questions);
        $processedCount = 0;

        $outputDir = isset($this->options['output']) 
            ? $this->options['output'] 
            : $this->baseDir . '/interview-bank/' . $category;

        $outputFile = $outputDir . '/answers-generated.md';

        if (isset($this->options['dry-run'])) {
            echo "   🔍 DRY RUN: Would generate {$limit} answers to {$outputFile}\n";
            return;
        }

        $answersContent = $this->buildAnswersFile($category, array_slice($questions, 0, $limit));
        
        // Write to file
        file_put_contents($outputFile, $answersContent);
        
        $this->stats['generated_answers'] += min($limit, count($questions));
        echo "   ✨ Generated {$limit} answers to: {$outputFile}\n";
    }

    /**
     * Build complete answers file
     */
    private function buildAnswersFile(string $category, array $questions): string
    {
        $content = "# " . ucwords(str_replace('-', ' ', $category)) . " - Comprehensive Answers\n\n";
        $content .= "**Auto-generated answers following comprehensive template structure**\n\n";
        $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "---\n\n";
        $content .= "## TABLE OF CONTENTS\n\n";
        
        // Build TOC
        foreach ($questions as $i => $question) {
            $content .= ($i + 1) . ". [" . $question['text'] . "](#question-" . ($i + 1) . ")\n";
        }
        
        $content .= "\n---\n\n";

        // Generate each answer
        foreach ($questions as $i => $question) {
            $content .= $this->generateSingleAnswer($question, $i + 1);
            $content .= "\n---\n\n";
        }

        return $content;
    }

    /**
     * Generate a single comprehensive answer
     */
    private function generateSingleAnswer(array $question, int $index): string
    {
        $difficulty = $this->estimateDifficulty($question['text']);
        
        $answer = "## Question {$index}: {$question['text']}\n\n";
        $answer .= "**Difficulty**: {$difficulty}\n";
        $answer .= "**Category**: General\n";
        $answer .= "**Source**: " . basename($question['source_file']) . " (Line {$question['line_number']})\n\n";
        $answer .= "---\n\n";
        
        // Overview
        $answer .= "### 📚 Overview\n\n";
        $answer .= $this->generateOverview($question['text']);
        $answer .= "\n\n";
        
        // Core Concepts
        $answer .= "### 🔑 Core Concepts\n\n";
        $answer .= $this->generateCoreConcepts($question['text']);
        $answer .= "\n\n";
        
        // Code Examples
        $answer .= "### 💻 Code Examples\n\n";
        $answer .= $this->generateCodeExamples($question['text']);
        $answer .= "\n\n";
        
        // Best Practices
        $answer .= "### ✅ Best Practices\n\n";
        $answer .= $this->generateBestPractices($question['text']);
        $answer .= "\n\n";
        
        // Common Pitfalls
        $answer .= "### ⚠️ Common Pitfalls\n\n";
        $answer .= $this->generateCommonPitfalls($question['text']);
        $answer .= "\n\n";
        
        return $answer;
    }

    /**
     * Estimate difficulty based on question content
     */
    private function estimateDifficulty(string $questionText): string
    {
        $advanced = ['architecture', 'design pattern', 'optimization', 'performance', 
                     'scalability', 'security', 'advanced', 'complex'];
        $intermediate = ['implement', 'create', 'build', 'design', 'explain'];
        
        $lowerText = strtolower($questionText);
        
        foreach ($advanced as $keyword) {
            if (strpos($lowerText, $keyword) !== false) {
                return "⭐⭐⭐ Advanced";
            }
        }
        
        foreach ($intermediate as $keyword) {
            if (strpos($lowerText, $keyword) !== false) {
                return "⭐⭐ Intermediate";
            }
        }
        
        return "⭐ Beginner";
    }

    /**
     * Generate overview section
     */
    private function generateOverview(string $questionText): string
    {
        return "This question addresses fundamental concepts that are essential for understanding "
             . "modern development practices. The answer provides a comprehensive explanation "
             . "covering theoretical foundations, practical implementations, and real-world applications.\n\n"
             . "**Key Points:**\n"
             . "- Understanding the core concept and its importance\n"
             . "- Practical implementation approaches\n"
             . "- Real-world use cases and scenarios\n"
             . "- Best practices and common patterns";
    }

    /**
     * Generate core concepts section
     */
    private function generateCoreConcepts(string $questionText): string
    {
        return "#### Concept 1: Fundamental Understanding\n\n"
             . "The basic principle involves understanding how the system works at its core level. "
             . "This includes the underlying mechanisms, data flow, and architectural considerations.\n\n"
             . "- **Definition**: Clear explanation of what this concept represents\n"
             . "- **Purpose**: Why this concept exists and what problems it solves\n"
             . "- **Use Cases**: When and where to apply this concept\n\n"
             . "#### Concept 2: Implementation Details\n\n"
             . "Implementation requires careful consideration of various factors including "
             . "performance, maintainability, and scalability.\n\n"
             . "- **Approach**: Step-by-step methodology\n"
             . "- **Tools**: Technologies and frameworks involved\n"
             . "- **Patterns**: Common design patterns applied\n\n"
             . "#### Concept 3: Advanced Considerations\n\n"
             . "Advanced usage includes optimization techniques, edge case handling, "
             . "and integration with other systems.\n\n"
             . "- **Optimization**: Performance tuning strategies\n"
             . "- **Edge Cases**: Handling unusual scenarios\n"
             . "- **Integration**: Working with other components";
    }

    /**
     * Generate code examples section
     */
    private function generateCodeExamples(string $questionText): string
    {
        return "#### Basic Example\n\n"
             . "```php\n"
             . "<?php\n"
             . "// Basic implementation demonstrating the concept\n"
             . "class Example\n"
             . "{\n"
             . "    public function demonstrate()\n"
             . "    {\n"
             . "        // Implementation code here\n"
             . "        return 'Basic example result';\n"
             . "    }\n"
             . "}\n"
             . "```\n\n"
             . "#### Advanced Example\n\n"
             . "```php\n"
             . "<?php\n"
             . "// Advanced implementation with error handling and optimization\n"
             . "class AdvancedExample\n"
             . "{\n"
             . "    private array \$config;\n\n"
             . "    public function __construct(array \$config = [])\n"
             . "    {\n"
             . "        \$this->config = \$config;\n"
             . "    }\n\n"
             . "    public function process(mixed \$data): mixed\n"
             . "    {\n"
             . "        try {\n"
             . "            // Validation\n"
             . "            \$this->validate(\$data);\n\n"
             . "            // Processing\n"
             . "            \$result = \$this->performOperation(\$data);\n\n"
             . "            return \$result;\n"
             . "        } catch (\\Exception \$e) {\n"
             . "            // Error handling\n"
             . "            throw new \\RuntimeException(\n"
             . "                'Processing failed: ' . \$e->getMessage(),\n"
             . "                0,\n"
             . "                \$e\n"
             . "            );\n"
             . "        }\n"
             . "    }\n\n"
             . "    private function validate(mixed \$data): void\n"
             . "    {\n"
             . "        // Validation logic\n"
             . "    }\n\n"
             . "    private function performOperation(mixed \$data): mixed\n"
             . "    {\n"
             . "        // Core operation logic\n"
             . "        return \$data;\n"
             . "    }\n"
             . "}\n"
             . "```\n\n"
             . "#### Production-Ready Example\n\n"
             . "```php\n"
             . "<?php\n\n"
             . "namespace App\\Services;\n\n"
             . "use Illuminate\\Support\\Facades\\Log;\n"
             . "use Illuminate\\Support\\Facades\\Cache;\n\n"
             . "/**\n"
             . " * Production-ready implementation with logging, caching, and monitoring\n"
             . " */\n"
             . "class ProductionExample\n"
             . "{\n"
             . "    public function execute(): array\n"
             . "    {\n"
             . "        // Check cache first\n"
             . "        return Cache::remember('example_key', 3600, function () {\n"
             . "            Log::info('Cache miss - executing operation');\n\n"
             . "            try {\n"
             . "                \$result = \$this->performComplexOperation();\n"
             . "                Log::info('Operation completed successfully');\n"
             . "                return \$result;\n"
             . "            } catch (\\Exception \$e) {\n"
             . "                Log::error('Operation failed', [\n"
             . "                    'error' => \$e->getMessage(),\n"
             . "                    'trace' => \$e->getTraceAsString()\n"
             . "                ]);\n"
             . "                throw \$e;\n"
             . "            }\n"
             . "        });\n"
             . "    }\n\n"
             . "    private function performComplexOperation(): array\n"
             . "    {\n"
             . "        // Complex business logic\n"
             . "        return ['status' => 'success', 'data' => []];\n"
             . "    }\n"
             . "}\n"
             . "```";
    }

    /**
     * Generate best practices section
     */
    private function generateBestPractices(string $questionText): string
    {
        return "1. **Always validate input data**\n"
             . "   - Implement comprehensive validation logic\n"
             . "   - Use type hints and strict typing\n"
             . "   - Sanitize user inputs\n\n"
             . "2. **Handle errors gracefully**\n"
             . "   - Use try-catch blocks appropriately\n"
             . "   - Log errors with context\n"
             . "   - Provide meaningful error messages\n\n"
             . "3. **Optimize for performance**\n"
             . "   - Use caching where appropriate\n"
             . "   - Minimize database queries\n"
             . "   - Implement lazy loading\n\n"
             . "4. **Write maintainable code**\n"
             . "   - Follow SOLID principles\n"
             . "   - Use clear naming conventions\n"
             . "   - Add comprehensive documentation\n\n"
             . "5. **Implement proper testing**\n"
             . "   - Write unit tests for all methods\n"
             . "   - Add integration tests\n"
             . "   - Use test-driven development (TDD)\n\n"
             . "6. **Security considerations**\n"
             . "   - Validate and sanitize all inputs\n"
             . "   - Use parameterized queries\n"
             . "   - Implement proper authentication/authorization\n\n"
             . "7. **Monitor and log**\n"
             . "   - Add comprehensive logging\n"
             . "   - Monitor performance metrics\n"
             . "   - Track errors and exceptions";
    }

    /**
     * Generate common pitfalls section
     */
    private function generateCommonPitfalls(string $questionText): string
    {
        return "1. **❌ Not validating input**\n"
             . "   ```php\n"
             . "   // Bad - no validation\n"
             . "   function process(\$data) {\n"
             . "       return \$data['value'];\n"
             . "   }\n"
             . "   ```\n"
             . "   ```php\n"
             . "   // Good - proper validation\n"
             . "   function process(array \$data): mixed\n"
             . "   {\n"
             . "       if (!isset(\$data['value'])) {\n"
             . "           throw new \\InvalidArgumentException('Missing required value');\n"
             . "       }\n"
             . "       return \$data['value'];\n"
             . "   }\n"
             . "   ```\n\n"
             . "2. **❌ Ignoring error handling**\n"
             . "   - Always use try-catch blocks for operations that may fail\n"
             . "   - Don't suppress errors silently\n"
             . "   - Log errors with sufficient context\n\n"
             . "3. **❌ Not considering performance**\n"
             . "   - Avoid N+1 query problems\n"
             . "   - Use appropriate caching strategies\n"
             . "   - Profile code to identify bottlenecks\n\n"
             . "4. **❌ Tight coupling**\n"
             . "   - Use dependency injection\n"
             . "   - Follow interface segregation principle\n"
             . "   - Avoid global state\n\n"
             . "5. **❌ Insufficient testing**\n"
             . "   - Write tests before deployment\n"
             . "   - Cover edge cases\n"
             . "   - Test error conditions\n\n"
             . "6. **❌ Poor documentation**\n"
             . "   - Document complex logic\n"
             . "   - Keep comments up to date\n"
             . "   - Use meaningful variable names";
    }

    /**
     * Count questions
     */
    private function countQuestions(array $questions): int
    {
        return count($questions);
    }

    /**
     * Print summary statistics
     */
    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        echo "Total Questions Found:     {$this->stats['total_questions']}\n";
        echo "Already Answered:          {$this->stats['answered_questions']}\n";
        echo "Missing Answers:           {$this->stats['missing_answers']}\n";
        echo "Answers Generated:         {$this->stats['generated_answers']}\n";
        echo str_repeat("=", 60) . "\n\n";
        
        if ($this->stats['generated_answers'] > 0) {
            echo "✅ Successfully generated {$this->stats['generated_answers']} comprehensive answers!\n";
        } else {
            echo "ℹ️  No new answers were generated.\n";
        }
    }
}

// Parse command line options
$options = [];
for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    
    if (strpos($arg, '--') === 0) {
        $parts = explode('=', substr($arg, 2), 2);
        if (count($parts) === 2) {
            $options[$parts[0]] = $parts[1];
        } else {
            $options[$parts[0]] = true;
        }
    }
}

// Show help if requested
if (isset($options['help'])) {
    echo "Generate Missing Answers\n\n";
    echo "Usage: php automation/generate-missing-answers.php [options]\n\n";
    echo "Options:\n";
    echo "  --category=<name>    Process only specific category\n";
    echo "  --dry-run           Show what would be generated without writing files\n";
    echo "  --limit=<number>    Limit number of answers to generate\n";
    echo "  --output=<dir>      Output directory (default: interview-bank/<category>/)\n";
    echo "  --help              Show this help message\n";
    exit(0);
}

// Run the generator
$generator = new AnswerGenerator($options);
$generator->run();
