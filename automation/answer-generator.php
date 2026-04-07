<?php

namespace InterviewBank\Automation;

class AnswerGenerator
{
    private string $baseDir;
    private array $categories = [];
    private array $unansweredQuestions = [];
    private array $statistics = [];

    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__) . '/interview-bank';
    }

    /**
     * Main method to analyze all categories and identify unanswered questions
     */
    public function analyzeUnansweredQuestions(): array
    {
        $this->categories = $this->findCategories();
        
        foreach ($this->categories as $category) {
            $this->processCategory($category);
        }

        $this->calculateStatistics();

        return [
            'categories' => $this->categories,
            'unanswered_questions' => $this->unansweredQuestions,
            'statistics' => $this->statistics
        ];
    }

    /**
     * Find all category directories with questions.md files
     */
    private function findCategories(): array
    {
        $categories = [];
        
        if (!is_dir($this->baseDir)) {
            return $categories;
        }

        $dirs = array_diff(scandir($this->baseDir), ['.', '..']);
        
        foreach ($dirs as $dir) {
            $path = $this->baseDir . '/' . $dir;
            
            if (is_dir($path)) {
                $questionsFile = $path . '/questions.md';
                
                if (file_exists($questionsFile)) {
                    $categories[] = [
                        'name' => $dir,
                        'path' => $path,
                        'questions_file' => $questionsFile,
                        'answers_files' => $this->findAnswerFiles($path)
                    ];
                }
            }
        }

        return $categories;
    }

    /**
     * Find all answer files in a category directory
     */
    private function findAnswerFiles(string $path): array
    {
        $answerFiles = [];
        $files = array_diff(scandir($path), ['.', '..']);
        
        foreach ($files as $file) {
            if (preg_match('/^answers.*\.md$/i', $file)) {
                $answerFiles[] = $path . '/' . $file;
            }
        }

        sort($answerFiles);
        return $answerFiles;
    }

    /**
     * Process a single category to identify unanswered questions
     */
    private function processCategory(array $category): void
    {
        $questions = $this->extractQuestions($category['questions_file']);
        $answeredQuestionIds = [];

        foreach ($category['answers_files'] as $answerFile) {
            $answered = $this->extractAnsweredQuestionIds($answerFile);
            $answeredQuestionIds = array_merge($answeredQuestionIds, $answered);
        }

        $answeredQuestionIds = array_unique($answeredQuestionIds);
        sort($answeredQuestionIds, SORT_NUMERIC);

        $unanswered = [];
        foreach ($questions as $question) {
            if (!in_array($question['id'], $answeredQuestionIds)) {
                $unanswered[] = $question;
            }
        }

        if (!empty($unanswered)) {
            $this->unansweredQuestions[$category['name']] = [
                'category_path' => $category['path'],
                'total_questions' => count($questions),
                'answered_count' => count($answeredQuestionIds),
                'unanswered_count' => count($unanswered),
                'coverage_percentage' => count($questions) > 0 
                    ? round((count($answeredQuestionIds) / count($questions)) * 100, 2) 
                    : 0,
                'unanswered_questions' => $unanswered,
                'answered_question_ids' => $answeredQuestionIds,
                'missing_ranges' => $this->findMissingRanges($questions, $answeredQuestionIds)
            ];
        } else {
            $this->unansweredQuestions[$category['name']] = [
                'category_path' => $category['path'],
                'total_questions' => count($questions),
                'answered_count' => count($answeredQuestionIds),
                'unanswered_count' => 0,
                'coverage_percentage' => 100.0,
                'unanswered_questions' => [],
                'answered_question_ids' => $answeredQuestionIds,
                'missing_ranges' => []
            ];
        }
    }

    /**
     * Extract questions from questions.md file with IDs
     */
    private function extractQuestions(string $filePath): array
    {
        $questions = [];
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            // Match numbered questions like "1. Question text" or "123. Question text"
            if (preg_match('/^(\d+)\.\s+(.+)$/', trim($line), $matches)) {
                $id = (int)$matches[1];
                $text = $matches[2];
                
                $questions[] = [
                    'id' => $id,
                    'text' => $text,
                    'line_number' => $lineNum + 1
                ];
            }
        }

        usort($questions, fn($a, $b) => $a['id'] <=> $b['id']);
        return $questions;
    }

    /**
     * Extract answered question IDs from an answers.md file
     */
    private function extractAnsweredQuestionIds(string $filePath): array
    {
        $answeredIds = [];
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            // Match patterns like "### Q1:", "## Q123:", "**Q1:**", etc.
            if (preg_match('/(?:#{1,6}\s*Q(\d+)|Q(\d+):|Question\s+(\d+))/i', $line, $matches)) {
                $id = (int)($matches[1] ?: $matches[2] ?: $matches[3]);
                if ($id > 0) {
                    $answeredIds[] = $id;
                }
            }
        }

        return array_unique($answeredIds);
    }

    /**
     * Find missing ranges in answered questions
     */
    private function findMissingRanges(array $allQuestions, array $answeredIds): array
    {
        if (empty($allQuestions)) {
            return [];
        }

        $allIds = array_map(fn($q) => $q['id'], $allQuestions);
        sort($allIds, SORT_NUMERIC);
        sort($answeredIds, SORT_NUMERIC);

        $missingIds = array_diff($allIds, $answeredIds);
        
        if (empty($missingIds)) {
            return [];
        }

        sort($missingIds, SORT_NUMERIC);
        
        $ranges = [];
        $rangeStart = $missingIds[0];
        $rangeLast = $missingIds[0];

        for ($i = 1; $i < count($missingIds); $i++) {
            if ($missingIds[$i] == $rangeLast + 1) {
                $rangeLast = $missingIds[$i];
            } else {
                $ranges[] = $rangeStart == $rangeLast 
                    ? "Q{$rangeStart}" 
                    : "Q{$rangeStart}-Q{$rangeLast}";
                $rangeStart = $missingIds[$i];
                $rangeLast = $missingIds[$i];
            }
        }

        $ranges[] = $rangeStart == $rangeLast 
            ? "Q{$rangeStart}" 
            : "Q{$rangeStart}-Q{$rangeLast}";

        return $ranges;
    }

    /**
     * Calculate overall statistics
     */
    private function calculateStatistics(): void
    {
        $totalQuestions = 0;
        $totalAnswered = 0;
        $totalUnanswered = 0;
        $categoryCoverages = [];

        foreach ($this->unansweredQuestions as $category => $data) {
            $totalQuestions += $data['total_questions'];
            $totalAnswered += $data['answered_count'];
            $totalUnanswered += $data['unanswered_count'];
            $categoryCoverages[$category] = $data['coverage_percentage'];
        }

        $this->statistics = [
            'total_categories' => count($this->unansweredQuestions),
            'total_questions' => $totalQuestions,
            'total_answered' => $totalAnswered,
            'total_unanswered' => $totalUnanswered,
            'overall_coverage_percentage' => $totalQuestions > 0 
                ? round(($totalAnswered / $totalQuestions) * 100, 2) 
                : 0,
            'category_coverages' => $categoryCoverages,
            'categories_complete' => count(array_filter($categoryCoverages, fn($c) => $c >= 100)),
            'categories_incomplete' => count(array_filter($categoryCoverages, fn($c) => $c < 100))
        ];
    }

    /**
     * Generate a detailed report in markdown format
     */
    public function generateMarkdownReport(): string
    {
        $data = $this->analyzeUnansweredQuestions();
        
        $report = "# Unanswered Questions Report\n\n";
        $report .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        $report .= "## Overall Statistics\n\n";
        $report .= "- **Total Categories**: {$this->statistics['total_categories']}\n";
        $report .= "- **Total Questions**: {$this->statistics['total_questions']}\n";
        $report .= "- **Total Answered**: {$this->statistics['total_answered']}\n";
        $report .= "- **Total Unanswered**: {$this->statistics['total_unanswered']}\n";
        $report .= "- **Overall Coverage**: {$this->statistics['overall_coverage_percentage']}%\n";
        $report .= "- **Complete Categories**: {$this->statistics['categories_complete']}\n";
        $report .= "- **Incomplete Categories**: {$this->statistics['categories_incomplete']}\n\n";
        
        $report .= "## Category Breakdown\n\n";
        
        foreach ($this->unansweredQuestions as $category => $data) {
            $report .= "### {$category}\n\n";
            $report .= "- **Path**: `{$data['category_path']}`\n";
            $report .= "- **Total Questions**: {$data['total_questions']}\n";
            $report .= "- **Answered**: {$data['answered_count']}\n";
            $report .= "- **Unanswered**: {$data['unanswered_count']}\n";
            $report .= "- **Coverage**: {$data['coverage_percentage']}%\n";
            
            if (!empty($data['missing_ranges'])) {
                $report .= "- **Missing Ranges**: " . implode(', ', $data['missing_ranges']) . "\n";
            }
            
            $report .= "\n";
            
            if (!empty($data['unanswered_questions'])) {
                $report .= "#### Unanswered Questions:\n\n";
                foreach ($data['unanswered_questions'] as $question) {
                    $report .= "- **Q{$question['id']}**: {$question['text']}\n";
                }
                $report .= "\n";
            } else {
                $report .= "✅ **All questions answered!**\n\n";
            }
        }
        
        return $report;
    }

    /**
     * Generate a JSON report
     */
    public function generateJsonReport(): string
    {
        $data = $this->analyzeUnansweredQuestions();
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate a mapping of questions to write
     */
    public function generateQuestionMapping(): array
    {
        $this->analyzeUnansweredQuestions();
        
        $mapping = [];
        
        foreach ($this->unansweredQuestions as $category => $data) {
            if ($data['unanswered_count'] > 0) {
                $mapping[$category] = [
                    'priority' => $this->calculatePriority($data),
                    'unanswered_count' => $data['unanswered_count'],
                    'coverage_percentage' => $data['coverage_percentage'],
                    'questions' => array_map(function($q) {
                        return [
                            'id' => $q['id'],
                            'text' => $q['text']
                        ];
                    }, $data['unanswered_questions'])
                ];
            }
        }

        // Sort by priority (lowest coverage first)
        uasort($mapping, fn($a, $b) => $a['priority'] <=> $b['priority']);
        
        return $mapping;
    }

    /**
     * Calculate priority for answering questions (lower = higher priority)
     */
    private function calculatePriority(array $categoryData): int
    {
        // Priority is based on coverage percentage (lower coverage = higher priority)
        // and number of unanswered questions
        return (int)($categoryData['coverage_percentage'] * 100) + $categoryData['unanswered_count'];
    }

    /**
     * Save report to file
     */
    public function saveReport(string $format = 'markdown', ?string $outputPath = null): string
    {
        $outputDir = $outputPath ?? __DIR__ . '/output';
        
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        
        switch ($format) {
            case 'json':
                $filename = "{$outputDir}/unanswered-questions_{$timestamp}.json";
                $content = $this->generateJsonReport();
                break;
            case 'mapping':
                $filename = "{$outputDir}/question-mapping_{$timestamp}.json";
                $content = json_encode($this->generateQuestionMapping(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                break;
            case 'markdown':
            default:
                $filename = "{$outputDir}/unanswered-questions_{$timestamp}.md";
                $content = $this->generateMarkdownReport();
                break;
        }

        file_put_contents($filename, $content);
        return $filename;
    }

    /**
     * Get the results of the analysis
     */
    public function getResults(): array
    {
        return [
            'categories' => $this->categories,
            'unanswered_questions' => $this->unansweredQuestions,
            'statistics' => $this->statistics
        ];
    }
}

// CLI execution
if (php_sapi_name() === 'cli' && isset($argv) && basename($argv[0]) === 'answer-generator.php') {
    echo "Interview Bank Answer Generator\n";
    echo "================================\n\n";

    $baseDir = $argv[1] ?? null;
    $generator = new AnswerGenerator($baseDir);

    echo "Analyzing categories and questions...\n\n";
    $results = $generator->analyzeUnansweredQuestions();

    echo "Statistics:\n";
    echo "- Total Categories: {$generator->getResults()['statistics']['total_categories']}\n";
    echo "- Total Questions: {$generator->getResults()['statistics']['total_questions']}\n";
    echo "- Total Answered: {$generator->getResults()['statistics']['total_answered']}\n";
    echo "- Total Unanswered: {$generator->getResults()['statistics']['total_unanswered']}\n";
    echo "- Overall Coverage: {$generator->getResults()['statistics']['overall_coverage_percentage']}%\n\n";

    // Save reports
    $markdownFile = $generator->saveReport('markdown');
    echo "Markdown report saved to: {$markdownFile}\n";

    $jsonFile = $generator->saveReport('json');
    echo "JSON report saved to: {$jsonFile}\n";

    $mappingFile = $generator->saveReport('mapping');
    echo "Question mapping saved to: {$mappingFile}\n";

    echo "\nDone!\n";
}
