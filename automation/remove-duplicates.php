<?php

/**
 * Remove Duplicates Script
 * 
 * Processes a deduplication report, automatically removes high-confidence duplicates 
 * (>95% similarity), preserves original question numbering with gap markers for 
 * removed questions, and outputs cleaned questions file with statistics.
 * 
 * Usage:
 *   php remove-duplicates.php <deduplication-report.json> <questions-file> [options]
 * 
 * Options:
 *   --output=<file>              Output cleaned questions file (default: questions-cleaned.md)
 *   --threshold=<number>         Similarity threshold for auto-removal (default: 95)
 *   --stats=<file>               Output statistics report file (default: removal-stats.json)
 *   --markdown-stats=<file>      Output markdown statistics report
 *   --preserve-numbering         Preserve original numbering with gap markers (default: true)
 *   --dry-run                    Show what would be removed without modifying files
 * 
 * Examples:
 *   php remove-duplicates.php deduplication-report.json questions.md
 *   php remove-duplicates.php report.json questions.md --threshold=98 --dry-run
 *   php remove-duplicates.php report.json questions.md --output=cleaned.md --stats=stats.json
 */

class DuplicateRemover
{
    private array $report = [];
    private array $questions = [];
    private array $questionsToRemove = [];
    private array $statistics = [];
    private float $threshold;
    private bool $preserveNumbering;
    private bool $dryRun;
    
    public function __construct(
        float $threshold = 95.0,
        bool $preserveNumbering = true,
        bool $dryRun = false
    ) {
        $this->threshold = $threshold;
        $this->preserveNumbering = $preserveNumbering;
        $this->dryRun = $dryRun;
        $this->initializeStatistics();
    }
    
    private function initializeStatistics(): void
    {
        $this->statistics = [
            'original_count' => 0,
            'removed_count' => 0,
            'retained_count' => 0,
            'removal_reason' => [
                'high_confidence_duplicate' => 0,
                'against_existing' => 0,
            ],
            'similarity_ranges' => [
                '100%' => 0,
                '99-95%' => 0,
            ],
            'removed_questions' => [],
            'gap_markers' => [],
        ];
    }
    
    /**
     * Load deduplication report
     */
    public function loadReport(string $reportFile): void
    {
        if (!file_exists($reportFile)) {
            throw new Exception("Report file not found: {$reportFile}");
        }
        
        $content = file_get_contents($reportFile);
        $this->report = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse JSON report: " . json_last_error_msg());
        }
        
        if (!isset($this->report['duplicates'])) {
            throw new Exception("Invalid report format: 'duplicates' key not found");
        }
    }
    
    /**
     * Load questions from file
     */
    public function loadQuestions(string $questionsFile): void
    {
        if (!file_exists($questionsFile)) {
            throw new Exception("Questions file not found: {$questionsFile}");
        }
        
        $content = file_get_contents($questionsFile);
        $this->questions = $this->parseQuestions($content);
        $this->statistics['original_count'] = count($this->questions);
    }
    
    /**
     * Parse questions from content
     */
    private function parseQuestions(string $content): array
    {
        $questions = [];
        $lines = explode("\n", $content);
        $currentSection = null;
        $questionNumber = 0;
        
        foreach ($lines as $lineNum => $line) {
            $originalLine = $line;
            $line = trim($line);
            
            // Track section headers
            if (preg_match('/^###\s+(.+)/', $line, $matches)) {
                $currentSection = trim($matches[1]);
                $questions[] = [
                    'type' => 'section_header',
                    'text' => $originalLine,
                    'section' => $currentSection,
                    'line_number' => $lineNum + 1,
                ];
                continue;
            }
            
            // Track main headers
            if (preg_match('/^#\s+(.+)/', $line, $matches)) {
                $questions[] = [
                    'type' => 'header',
                    'text' => $originalLine,
                    'line_number' => $lineNum + 1,
                ];
                continue;
            }
            
            // Detect numbered questions: "1. Question text"
            if (preg_match('/^(\d+)\.\s+(.+)/', $line, $matches)) {
                $questionNumber = (int)$matches[1];
                $questionText = trim($matches[2]);
                
                $questions[] = [
                    'type' => 'question',
                    'number' => $questionNumber,
                    'text' => $questionText,
                    'original_line' => $originalLine,
                    'section' => $currentSection,
                    'line_number' => $lineNum + 1,
                ];
                continue;
            }
            
            // Empty lines and other content
            if (empty($line)) {
                $questions[] = [
                    'type' => 'empty',
                    'text' => $originalLine,
                    'line_number' => $lineNum + 1,
                ];
            } else {
                $questions[] = [
                    'type' => 'content',
                    'text' => $originalLine,
                    'line_number' => $lineNum + 1,
                ];
            }
        }
        
        return $questions;
    }
    
    /**
     * Identify questions to remove based on report
     */
    public function identifyQuestionsToRemove(): void
    {
        foreach ($this->report['duplicates'] as $duplicate) {
            $similarity = $duplicate['similarity']['average_score'];
            
            // Only auto-remove if similarity is above threshold
            if ($similarity < $this->threshold) {
                continue;
            }
            
            $questionToRemove = null;
            $reason = '';
            
            if ($duplicate['type'] === 'against_existing') {
                // Remove from raw questions if it exists in existing
                $questionToRemove = $duplicate['question_1']['id'];
                $reason = 'against_existing';
                $this->statistics['removal_reason']['against_existing']++;
            } elseif ($duplicate['type'] === 'within_raw') {
                // Remove the second occurrence (keep first)
                $questionToRemove = $duplicate['question_2']['id'];
                $reason = 'high_confidence_duplicate';
                $this->statistics['removal_reason']['high_confidence_duplicate']++;
            }
            
            if ($questionToRemove !== null) {
                $this->questionsToRemove[$questionToRemove] = [
                    'id' => $questionToRemove,
                    'text' => $duplicate['question_1']['text'] ?? $duplicate['question_2']['text'],
                    'similarity' => $similarity,
                    'reason' => $reason,
                    'duplicate_of' => $duplicate['type'] === 'against_existing' 
                        ? ($duplicate['question_2']['source_file'] ?? $duplicate['question_2']['source'])
                        : $duplicate['question_1']['id'],
                ];
                
                // Update similarity ranges
                if ($similarity >= 100) {
                    $this->statistics['similarity_ranges']['100%']++;
                } elseif ($similarity >= 95) {
                    $this->statistics['similarity_ranges']['99-95%']++;
                }
            }
        }
        
        $this->statistics['removed_count'] = count($this->questionsToRemove);
        $this->statistics['retained_count'] = 
            $this->statistics['original_count'] - $this->statistics['removed_count'];
    }
    
    /**
     * Generate cleaned questions content
     */
    public function generateCleanedContent(): string
    {
        $output = [];
        $removedInSection = [];
        
        foreach ($this->questions as $item) {
            if ($item['type'] === 'question') {
                $questionNumber = $item['number'];
                
                if (isset($this->questionsToRemove[$questionNumber])) {
                    // This question should be removed
                    $removal = $this->questionsToRemove[$questionNumber];
                    
                    if ($this->preserveNumbering) {
                        // Add gap marker
                        $marker = "<!-- Question {$questionNumber} removed (duplicate, " 
                            . round($removal['similarity'], 1) 
                            . "% similarity";
                        
                        if ($removal['reason'] === 'against_existing') {
                            $marker .= ", exists in: {$removal['duplicate_of']}";
                        } else {
                            $marker .= ", duplicate of question {$removal['duplicate_of']}";
                        }
                        
                        $marker .= ") -->";
                        $output[] = $marker;
                        $this->statistics['gap_markers'][] = $questionNumber;
                    }
                    
                    // Track removed question
                    $this->statistics['removed_questions'][] = [
                        'number' => $questionNumber,
                        'text' => $item['text'],
                        'similarity' => $removal['similarity'],
                        'reason' => $removal['reason'],
                        'section' => $item['section'] ?? 'Unknown',
                    ];
                    
                    continue;
                }
                
                // Keep the question
                $output[] = $item['original_line'];
                
            } else {
                // Keep all other content (headers, empty lines, etc.)
                $output[] = $item['text'];
            }
        }
        
        return implode("\n", $output);
    }
    
    /**
     * Write cleaned questions to file
     */
    public function writeCleanedQuestions(string $outputFile): void
    {
        if ($this->dryRun) {
            echo "[DRY RUN] Would write cleaned questions to: {$outputFile}\n";
            return;
        }
        
        $content = $this->generateCleanedContent();
        file_put_contents($outputFile, $content);
    }
    
    /**
     * Generate statistics report
     */
    public function generateStatisticsReport(): array
    {
        return [
            'metadata' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'threshold' => $this->threshold,
                'preserve_numbering' => $this->preserveNumbering,
                'dry_run' => $this->dryRun,
            ],
            'summary' => [
                'original_count' => $this->statistics['original_count'],
                'removed_count' => $this->statistics['removed_count'],
                'retained_count' => $this->statistics['retained_count'],
                'removal_percentage' => $this->statistics['original_count'] > 0 
                    ? round(($this->statistics['removed_count'] / $this->statistics['original_count']) * 100, 2)
                    : 0,
            ],
            'removal_breakdown' => [
                'by_reason' => $this->statistics['removal_reason'],
                'by_similarity' => $this->statistics['similarity_ranges'],
            ],
            'removed_questions' => $this->statistics['removed_questions'],
            'gap_markers' => $this->statistics['gap_markers'],
        ];
    }
    
    /**
     * Export statistics to JSON
     */
    public function exportStatisticsToJson(string $outputFile): void
    {
        if ($this->dryRun) {
            echo "[DRY RUN] Would write statistics to: {$outputFile}\n";
            return;
        }
        
        $stats = $this->generateStatisticsReport();
        
        $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $json = json_encode($stats, $jsonOptions);
        
        if ($json === false) {
            throw new Exception("Failed to encode JSON: " . json_last_error_msg());
        }
        
        file_put_contents($outputFile, $json);
    }
    
    /**
     * Export statistics to Markdown
     */
    public function exportStatisticsToMarkdown(string $outputFile): void
    {
        if ($this->dryRun) {
            echo "[DRY RUN] Would write markdown statistics to: {$outputFile}\n";
            return;
        }
        
        $stats = $this->generateStatisticsReport();
        
        $md = "# Duplicate Removal Statistics\n\n";
        $md .= "**Generated:** " . $stats['metadata']['generated_at'] . "\n";
        $md .= "**Similarity Threshold:** " . $stats['metadata']['threshold'] . "%\n";
        $md .= "**Preserve Numbering:** " . ($stats['metadata']['preserve_numbering'] ? 'Yes' : 'No') . "\n\n";
        
        $md .= "## Summary\n\n";
        $md .= "| Metric | Count |\n";
        $md .= "|--------|-------|\n";
        $md .= "| Original Questions | " . $stats['summary']['original_count'] . " |\n";
        $md .= "| Questions Removed | " . $stats['summary']['removed_count'] . " |\n";
        $md .= "| Questions Retained | " . $stats['summary']['retained_count'] . " |\n";
        $md .= "| Removal Percentage | " . $stats['summary']['removal_percentage'] . "% |\n\n";
        
        $md .= "## Removal Breakdown\n\n";
        $md .= "### By Reason\n\n";
        $md .= "| Reason | Count |\n";
        $md .= "|--------|-------|\n";
        foreach ($stats['removal_breakdown']['by_reason'] as $reason => $count) {
            $reasonLabel = ucwords(str_replace('_', ' ', $reason));
            $md .= "| {$reasonLabel} | {$count} |\n";
        }
        
        $md .= "\n### By Similarity\n\n";
        $md .= "| Range | Count |\n";
        $md .= "|-------|-------|\n";
        foreach ($stats['removal_breakdown']['by_similarity'] as $range => $count) {
            $md .= "| {$range} | {$count} |\n";
        }
        
        $md .= "\n## Removed Questions\n\n";
        if (!empty($stats['removed_questions'])) {
            $md .= "| # | Question | Reason | Similarity | Section |\n";
            $md .= "|---|----------|--------|------------|---------|\n";
            
            foreach ($stats['removed_questions'] as $q) {
                $questionPreview = strlen($q['text']) > 60 
                    ? substr($q['text'], 0, 60) . "..." 
                    : $q['text'];
                $reasonLabel = ucwords(str_replace('_', ' ', $q['reason']));
                $similarity = round($q['similarity'], 1) . "%";
                $section = $q['section'] ?? 'N/A';
                
                $md .= "| {$q['number']} | {$questionPreview} | {$reasonLabel} | {$similarity} | {$section} |\n";
            }
        } else {
            $md .= "*No questions were removed.*\n";
        }
        
        if (!empty($stats['gap_markers'])) {
            $md .= "\n## Gap Markers\n\n";
            $md .= "Gap markers were inserted for the following question numbers to preserve original numbering:\n\n";
            $md .= implode(', ', $stats['gap_markers']) . "\n";
        }
        
        file_put_contents($outputFile, $md);
    }
    
    /**
     * Print summary to console
     */
    public function printSummary(): void
    {
        $stats = $this->generateStatisticsReport();
        
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "DUPLICATE REMOVAL SUMMARY\n";
        echo str_repeat('=', 70) . "\n\n";
        
        if ($this->dryRun) {
            echo "*** DRY RUN MODE - No files will be modified ***\n\n";
        }
        
        echo "Original Questions:       " . $stats['summary']['original_count'] . "\n";
        echo "Questions Removed:        " . $stats['summary']['removed_count'] . "\n";
        echo "Questions Retained:       " . $stats['summary']['retained_count'] . "\n";
        echo "Removal Percentage:       " . $stats['summary']['removal_percentage'] . "%\n\n";
        
        echo "Removal Reasons:\n";
        foreach ($stats['removal_breakdown']['by_reason'] as $reason => $count) {
            $reasonLabel = ucwords(str_replace('_', ' ', $reason));
            echo sprintf("  %-30s: %d\n", $reasonLabel, $count);
        }
        
        echo "\nSimilarity Ranges:\n";
        foreach ($stats['removal_breakdown']['by_similarity'] as $range => $count) {
            if ($count > 0) {
                echo sprintf("  %-10s: %d\n", $range, $count);
            }
        }
        
        if ($this->preserveNumbering && !empty($stats['gap_markers'])) {
            echo "\nGap Markers: " . count($stats['gap_markers']) . " inserted\n";
        }
        
        echo "\n" . str_repeat('=', 70) . "\n\n";
    }
    
    /**
     * Print removed questions details
     */
    public function printRemovedQuestions(): void
    {
        if (empty($this->statistics['removed_questions'])) {
            echo "No questions were removed.\n";
            return;
        }
        
        echo "\nRemoved Questions Details:\n";
        echo str_repeat('-', 70) . "\n";
        
        foreach ($this->statistics['removed_questions'] as $i => $q) {
            echo "\n" . ($i + 1) . ". Question #{$q['number']}\n";
            echo "   Text: {$q['text']}\n";
            echo "   Reason: " . ucwords(str_replace('_', ' ', $q['reason'])) . "\n";
            echo "   Similarity: " . round($q['similarity'], 1) . "%\n";
            echo "   Section: " . ($q['section'] ?? 'N/A') . "\n";
        }
        
        echo "\n" . str_repeat('-', 70) . "\n";
    }
    
    /**
     * Run the removal process
     */
    public function run(): void
    {
        echo "Identifying questions to remove (threshold: {$this->threshold}%)...\n";
        $this->identifyQuestionsToRemove();
        echo "Found {$this->statistics['removed_count']} questions to remove.\n";
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $scriptName = basename(__FILE__);
    
    if ($argc < 3) {
        echo "Usage: php {$scriptName} <deduplication-report.json> <questions-file> [options]\n\n";
        echo "Arguments:\n";
        echo "  deduplication-report.json  Path to deduplication report JSON file\n";
        echo "  questions-file             Path to questions file to clean\n\n";
        echo "Options:\n";
        echo "  --output=<file>            Output cleaned questions file (default: questions-cleaned.md)\n";
        echo "  --threshold=<number>       Similarity threshold for auto-removal (default: 95)\n";
        echo "  --stats=<file>             Output statistics report file (default: removal-stats.json)\n";
        echo "  --markdown-stats=<file>    Output markdown statistics report\n";
        echo "  --dry-run                  Show what would be removed without modifying files\n";
        echo "  --show-removed             Show detailed list of removed questions\n\n";
        echo "Examples:\n";
        echo "  php {$scriptName} deduplication-report.json questions.md\n";
        echo "  php {$scriptName} report.json questions.md --threshold=98 --dry-run\n";
        echo "  php {$scriptName} report.json questions.md --output=cleaned.md --stats=stats.json\n";
        exit(1);
    }
    
    $reportFile = $argv[1];
    $questionsFile = $argv[2];
    $outputFile = 'questions-cleaned.md';
    $statsFile = 'removal-stats.json';
    $markdownStatsFile = null;
    $threshold = 95.0;
    $dryRun = false;
    $showRemoved = false;
    
    // Parse options
    for ($i = 3; $i < $argc; $i++) {
        if (strpos($argv[$i], '--output=') === 0) {
            $outputFile = substr($argv[$i], 9);
        } elseif (strpos($argv[$i], '--threshold=') === 0) {
            $threshold = (float)substr($argv[$i], 12);
        } elseif (strpos($argv[$i], '--stats=') === 0) {
            $statsFile = substr($argv[$i], 8);
        } elseif (strpos($argv[$i], '--markdown-stats=') === 0) {
            $markdownStatsFile = substr($argv[$i], 17);
        } elseif ($argv[$i] === '--dry-run') {
            $dryRun = true;
        } elseif ($argv[$i] === '--show-removed') {
            $showRemoved = true;
        }
    }
    
    try {
        echo "Duplicate Removal Script\n";
        echo "========================\n\n";
        echo "Report file: {$reportFile}\n";
        echo "Questions file: {$questionsFile}\n";
        echo "Similarity threshold: {$threshold}%\n";
        echo "Dry run: " . ($dryRun ? 'Yes' : 'No') . "\n\n";
        
        $remover = new DuplicateRemover($threshold, true, $dryRun);
        
        echo "Loading deduplication report...\n";
        $remover->loadReport($reportFile);
        
        echo "Loading questions...\n";
        $remover->loadQuestions($questionsFile);
        
        $remover->run();
        
        $remover->printSummary();
        
        if ($showRemoved) {
            $remover->printRemovedQuestions();
        }
        
        if (!$dryRun) {
            echo "Writing cleaned questions to: {$outputFile}\n";
            $remover->writeCleanedQuestions($outputFile);
            
            echo "Writing statistics to: {$statsFile}\n";
            $remover->exportStatisticsToJson($statsFile);
            
            if ($markdownStatsFile) {
                echo "Writing markdown statistics to: {$markdownStatsFile}\n";
                $remover->exportStatisticsToMarkdown($markdownStatsFile);
            }
            
            echo "\nDuplicate removal completed successfully!\n";
        } else {
            echo "\n[DRY RUN] No files were modified. Run without --dry-run to apply changes.\n";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
