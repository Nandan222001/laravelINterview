<?php

/**
 * Question Deduplication Script
 * 
 * Identifies duplicate questions within a raw list and against existing questions 
 * in interview-bank subdirectories using similar_text() and levenshtein() functions.
 * Generates a deduplication report showing original count, unique count, and merge 
 * recommendations with similarity scores.
 * 
 * Usage:
 *   php deduplicate-questions.php <raw-questions-file> [options]
 * 
 * Options:
 *   --threshold=<number>       Similarity threshold (0-100, default: 85)
 *   --output=<file>            Output report file (default: deduplication-report.json)
 *   --markdown=<file>          Generate markdown report
 *   --interactive              Interactive mode for reviewing duplicates
 *   --check-existing           Check against existing interview-bank questions
 * 
 * Examples:
 *   php deduplicate-questions.php raw-questions.txt
 *   php deduplicate-questions.php questions.md --threshold=90 --markdown=report.md
 *   php deduplicate-questions.php new-questions.txt --check-existing
 */

class QuestionDeduplicator
{
    private array $rawQuestions = [];
    private array $existingQuestions = [];
    private array $duplicates = [];
    private array $statistics = [];
    private float $similarityThreshold;
    private string $interviewBankPath;
    
    // Maximum string length for levenshtein (PHP limitation)
    private const LEVENSHTEIN_MAX_LENGTH = 255;
    
    public function __construct(float $similarityThreshold = 85.0)
    {
        $this->similarityThreshold = $similarityThreshold;
        $this->interviewBankPath = dirname(__DIR__) . '/interview-bank';
        $this->initializeStatistics();
    }
    
    private function initializeStatistics(): void
    {
        $this->statistics = [
            'raw_questions_count' => 0,
            'existing_questions_count' => 0,
            'unique_questions' => 0,
            'duplicate_groups' => 0,
            'total_duplicates' => 0,
            'duplicates_within_raw' => 0,
            'duplicates_against_existing' => 0,
            'similarity_distribution' => [
                '100%' => 0,
                '95-99%' => 0,
                '90-94%' => 0,
                '85-89%' => 0,
            ],
            'processing_time' => 0,
        ];
    }
    
    /**
     * Load questions from raw file
     */
    public function loadRawQuestions(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new Exception("Raw questions file not found: {$filePath}");
        }
        
        $content = file_get_contents($filePath);
        $this->rawQuestions = $this->parseQuestions($content, 'raw');
        $this->statistics['raw_questions_count'] = count($this->rawQuestions);
    }
    
    /**
     * Load existing questions from interview-bank directories
     */
    public function loadExistingQuestions(): void
    {
        if (!is_dir($this->interviewBankPath)) {
            throw new Exception("Interview bank path not found: {$this->interviewBankPath}");
        }
        
        $this->existingQuestions = [];
        $directories = glob($this->interviewBankPath . '/*', GLOB_ONLYDIR);
        
        foreach ($directories as $dir) {
            $questionFiles = glob($dir . '/questions*.md');
            
            foreach ($questionFiles as $file) {
                $content = file_get_contents($file);
                $questions = $this->parseQuestions($content, basename($dir));
                
                foreach ($questions as $question) {
                    $question['source_file'] = str_replace($this->interviewBankPath . '/', '', $file);
                    $this->existingQuestions[] = $question;
                }
            }
        }
        
        $this->statistics['existing_questions_count'] = count($this->existingQuestions);
    }
    
    /**
     * Parse questions from content
     */
    private function parseQuestions(string $content, string $source): array
    {
        $questions = [];
        $lines = explode("\n", $content);
        $questionNumber = 0;
        
        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Skip headers and separators
            if (preg_match('/^[#=\-]{2,}/', $line)) {
                continue;
            }
            
            // Detect numbered questions: "1. Question text"
            if (preg_match('/^(\d+)\.\s+(.+)/', $line, $matches)) {
                $questionNumber = (int)$matches[1];
                $questionText = trim($matches[2]);
            }
            // Detect bullet point questions: "- Question text" or "* Question text"
            elseif (preg_match('/^[-*]\s+(.+)/', $line, $matches)) {
                $questionNumber++;
                $questionText = trim($matches[1]);
            }
            // Skip non-question lines
            else {
                continue;
            }
            
            // Clean up and validate question
            $questionText = $this->cleanQuestionText($questionText);
            
            if (strlen($questionText) < 10) {
                continue;
            }
            
            $questions[] = [
                'id' => $questionNumber,
                'text' => $questionText,
                'normalized' => $this->normalizeQuestion($questionText),
                'source' => $source,
                'line_number' => $lineNum + 1,
            ];
        }
        
        return $questions;
    }
    
    /**
     * Clean question text
     */
    private function cleanQuestionText(string $text): string
    {
        // Remove markdown formatting
        $text = preg_replace('/[*_`#]/', '', $text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Normalize question for comparison
     */
    private function normalizeQuestion(string $text): string
    {
        // Convert to lowercase
        $normalized = strtolower($text);
        
        // Remove punctuation
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);
        
        // Normalize whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }
    
    /**
     * Calculate similarity between two questions
     */
    private function calculateSimilarity(string $text1, string $text2): array
    {
        $norm1 = $this->normalizeQuestion($text1);
        $norm2 = $this->normalizeQuestion($text2);
        
        // Calculate similarity using similar_text
        $similarTextPercent = 0;
        similar_text($norm1, $norm2, $similarTextPercent);
        
        // Calculate Levenshtein distance if strings are short enough
        $levenshteinScore = null;
        $levenshteinPercent = 0;
        
        if (strlen($norm1) <= self::LEVENSHTEIN_MAX_LENGTH && 
            strlen($norm2) <= self::LEVENSHTEIN_MAX_LENGTH) {
            
            $maxLen = max(strlen($norm1), strlen($norm2));
            
            if ($maxLen > 0) {
                $distance = levenshtein($norm1, $norm2);
                $levenshteinScore = $distance;
                $levenshteinPercent = (1 - ($distance / $maxLen)) * 100;
            }
        }
        
        // Use average of both algorithms when available, otherwise just similar_text
        $averageScore = $levenshteinScore !== null 
            ? ($similarTextPercent + $levenshteinPercent) / 2 
            : $similarTextPercent;
        
        return [
            'similar_text_percent' => round($similarTextPercent, 2),
            'levenshtein_score' => $levenshteinScore,
            'levenshtein_percent' => $levenshteinScore !== null ? round($levenshteinPercent, 2) : null,
            'average_score' => round($averageScore, 2),
        ];
    }
    
    /**
     * Find duplicates within raw questions
     */
    public function findDuplicatesWithinRaw(): void
    {
        $count = count($this->rawQuestions);
        
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $q1 = $this->rawQuestions[$i];
                $q2 = $this->rawQuestions[$j];
                
                $similarity = $this->calculateSimilarity($q1['text'], $q2['text']);
                
                if ($similarity['average_score'] >= $this->similarityThreshold) {
                    $this->addDuplicate('within_raw', $q1, $q2, $similarity);
                }
            }
        }
    }
    
    /**
     * Find duplicates against existing questions
     */
    public function findDuplicatesAgainstExisting(): void
    {
        foreach ($this->rawQuestions as $rawQuestion) {
            foreach ($this->existingQuestions as $existingQuestion) {
                $similarity = $this->calculateSimilarity(
                    $rawQuestion['text'], 
                    $existingQuestion['text']
                );
                
                if ($similarity['average_score'] >= $this->similarityThreshold) {
                    $this->addDuplicate('against_existing', $rawQuestion, $existingQuestion, $similarity);
                }
            }
        }
    }
    
    /**
     * Add duplicate to the list
     */
    private function addDuplicate(string $type, array $q1, array $q2, array $similarity): void
    {
        $duplicate = [
            'type' => $type,
            'question_1' => $q1,
            'question_2' => $q2,
            'similarity' => $similarity,
            'recommendation' => $this->generateRecommendation($similarity['average_score']),
        ];
        
        $this->duplicates[] = $duplicate;
        
        // Update statistics
        if ($type === 'within_raw') {
            $this->statistics['duplicates_within_raw']++;
        } else {
            $this->statistics['duplicates_against_existing']++;
        }
        
        $this->updateSimilarityDistribution($similarity['average_score']);
    }
    
    /**
     * Generate recommendation based on similarity score
     */
    private function generateRecommendation(float $score): string
    {
        if ($score >= 98) {
            return 'MERGE - Nearly identical, definitely duplicate';
        } elseif ($score >= 92) {
            return 'MERGE - Very similar, likely duplicate';
        } elseif ($score >= 87) {
            return 'REVIEW - Similar, possible duplicate';
        } else {
            return 'REVIEW - Somewhat similar, manual review needed';
        }
    }
    
    /**
     * Update similarity distribution statistics
     */
    private function updateSimilarityDistribution(float $score): void
    {
        if ($score >= 100) {
            $this->statistics['similarity_distribution']['100%']++;
        } elseif ($score >= 95) {
            $this->statistics['similarity_distribution']['95-99%']++;
        } elseif ($score >= 90) {
            $this->statistics['similarity_distribution']['90-94%']++;
        } elseif ($score >= 85) {
            $this->statistics['similarity_distribution']['85-89%']++;
        }
    }
    
    /**
     * Calculate final statistics
     */
    private function calculateFinalStatistics(): void
    {
        $this->statistics['total_duplicates'] = count($this->duplicates);
        
        // Count unique questions (raw questions minus duplicates within raw)
        $duplicateIds = [];
        foreach ($this->duplicates as $dup) {
            if ($dup['type'] === 'within_raw') {
                $duplicateIds[] = $dup['question_2']['id'];
            }
        }
        
        $duplicateIds = array_unique($duplicateIds);
        $this->statistics['unique_questions'] = $this->statistics['raw_questions_count'] - count($duplicateIds);
        
        // Count duplicate groups
        $this->statistics['duplicate_groups'] = $this->countDuplicateGroups();
    }
    
    /**
     * Count duplicate groups
     */
    private function countDuplicateGroups(): int
    {
        $groups = [];
        
        foreach ($this->duplicates as $dup) {
            if ($dup['type'] === 'within_raw') {
                $id1 = $dup['question_1']['id'];
                $id2 = $dup['question_2']['id'];
                
                $foundGroup = false;
                foreach ($groups as &$group) {
                    if (in_array($id1, $group) || in_array($id2, $group)) {
                        $group[] = $id1;
                        $group[] = $id2;
                        $group = array_unique($group);
                        $foundGroup = true;
                        break;
                    }
                }
                
                if (!$foundGroup) {
                    $groups[] = [$id1, $id2];
                }
            }
        }
        
        return count($groups);
    }
    
    /**
     * Generate deduplication report
     */
    public function generateReport(): array
    {
        $this->calculateFinalStatistics();
        
        // Sort duplicates by similarity score (highest first)
        usort($this->duplicates, function($a, $b) {
            return $b['similarity']['average_score'] <=> $a['similarity']['average_score'];
        });
        
        return [
            'metadata' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'similarity_threshold' => $this->similarityThreshold,
                'processing_time' => $this->statistics['processing_time'],
            ],
            'summary' => [
                'original_count' => $this->statistics['raw_questions_count'],
                'existing_count' => $this->statistics['existing_questions_count'],
                'unique_count' => $this->statistics['unique_questions'],
                'duplicate_groups' => $this->statistics['duplicate_groups'],
                'total_duplicates_found' => $this->statistics['total_duplicates'],
                'duplicates_within_raw' => $this->statistics['duplicates_within_raw'],
                'duplicates_against_existing' => $this->statistics['duplicates_against_existing'],
            ],
            'statistics' => $this->statistics,
            'duplicates' => $this->duplicates,
            'recommendations' => $this->generateMergeRecommendations(),
        ];
    }
    
    /**
     * Generate merge recommendations
     */
    private function generateMergeRecommendations(): array
    {
        $recommendations = [
            'high_confidence_merges' => [],
            'review_required' => [],
            'already_exists' => [],
        ];
        
        foreach ($this->duplicates as $dup) {
            $score = $dup['similarity']['average_score'];
            
            if ($dup['type'] === 'against_existing') {
                $recommendations['already_exists'][] = [
                    'question' => $dup['question_1']['text'],
                    'exists_in' => $dup['question_2']['source_file'] ?? $dup['question_2']['source'],
                    'similarity' => $score,
                ];
            } elseif ($score >= 92) {
                $recommendations['high_confidence_merges'][] = [
                    'keep' => $dup['question_1'],
                    'remove' => $dup['question_2'],
                    'similarity' => $score,
                ];
            } else {
                $recommendations['review_required'][] = [
                    'question_1' => $dup['question_1'],
                    'question_2' => $dup['question_2'],
                    'similarity' => $score,
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Export report to JSON
     */
    public function exportToJson(string $outputFile): void
    {
        $report = $this->generateReport();
        
        $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $json = json_encode($report, $jsonOptions);
        
        if ($json === false) {
            throw new Exception("Failed to encode JSON: " . json_last_error_msg());
        }
        
        file_put_contents($outputFile, $json);
    }
    
    /**
     * Export report to Markdown
     */
    public function exportToMarkdown(string $outputFile): void
    {
        $report = $this->generateReport();
        
        $md = "# Question Deduplication Report\n\n";
        $md .= "**Generated:** " . $report['metadata']['generated_at'] . "\n";
        $md .= "**Similarity Threshold:** " . $report['metadata']['similarity_threshold'] . "%\n";
        $md .= "**Processing Time:** " . round($report['metadata']['processing_time'], 2) . "s\n\n";
        
        $md .= "## Summary\n\n";
        $md .= "| Metric | Count |\n";
        $md .= "|--------|-------|\n";
        $md .= "| Original Questions | " . $report['summary']['original_count'] . " |\n";
        $md .= "| Unique Questions | " . $report['summary']['unique_count'] . " |\n";
        $md .= "| Duplicate Groups | " . $report['summary']['duplicate_groups'] . " |\n";
        $md .= "| Total Duplicates Found | " . $report['summary']['total_duplicates_found'] . " |\n";
        $md .= "| - Within Raw Questions | " . $report['summary']['duplicates_within_raw'] . " |\n";
        $md .= "| - Against Existing | " . $report['summary']['duplicates_against_existing'] . " |\n";
        
        if ($report['summary']['existing_count'] > 0) {
            $md .= "| Existing Questions Checked | " . $report['summary']['existing_count'] . " |\n";
        }
        
        $md .= "\n## Similarity Distribution\n\n";
        $md .= "| Range | Count |\n";
        $md .= "|-------|-------|\n";
        foreach ($report['statistics']['similarity_distribution'] as $range => $count) {
            $md .= "| {$range} | {$count} |\n";
        }
        
        $md .= "\n## High Confidence Merges\n\n";
        $highConfidence = $report['recommendations']['high_confidence_merges'];
        if (!empty($highConfidence)) {
            $md .= "*These questions are very similar and should likely be merged.*\n\n";
            foreach ($highConfidence as $i => $rec) {
                $md .= "### Merge " . ($i + 1) . " (Similarity: " . round($rec['similarity'], 1) . "%)\n\n";
                $md .= "**Keep:** " . $rec['keep']['text'] . "\n\n";
                $md .= "**Remove:** " . $rec['remove']['text'] . "\n\n";
            }
        } else {
            $md .= "*No high confidence merges found.*\n\n";
        }
        
        $md .= "## Questions Already in Interview Bank\n\n";
        $alreadyExists = $report['recommendations']['already_exists'];
        if (!empty($alreadyExists)) {
            $md .= "*These questions already exist in the interview bank.*\n\n";
            foreach ($alreadyExists as $i => $rec) {
                $md .= ($i + 1) . ". **" . $rec['question'] . "**\n";
                $md .= "   - Exists in: `" . $rec['exists_in'] . "`\n";
                $md .= "   - Similarity: " . round($rec['similarity'], 1) . "%\n\n";
            }
        } else {
            $md .= "*No duplicates found against existing questions.*\n\n";
        }
        
        $md .= "## Questions Requiring Review\n\n";
        $reviewRequired = $report['recommendations']['review_required'];
        if (!empty($reviewRequired)) {
            $md .= "*These questions are similar but require manual review.*\n\n";
            foreach (array_slice($reviewRequired, 0, 20) as $i => $rec) {
                $md .= "### Review " . ($i + 1) . " (Similarity: " . round($rec['similarity'], 1) . "%)\n\n";
                $md .= "**Question 1:** " . $rec['question_1']['text'] . "\n\n";
                $md .= "**Question 2:** " . $rec['question_2']['text'] . "\n\n";
            }
            
            if (count($reviewRequired) > 20) {
                $md .= "*... and " . (count($reviewRequired) - 20) . " more. See JSON report for full details.*\n\n";
            }
        } else {
            $md .= "*No questions requiring review.*\n\n";
        }
        
        $md .= "## All Duplicates\n\n";
        $md .= "| # | Type | Q1 | Q2 | Similarity | Recommendation |\n";
        $md .= "|---|------|----|----|------------|----------------|\n";
        
        foreach (array_slice($report['duplicates'], 0, 50) as $i => $dup) {
            $q1_preview = substr($dup['question_1']['text'], 0, 50) . "...";
            $q2_preview = substr($dup['question_2']['text'], 0, 50) . "...";
            $similarity = round($dup['similarity']['average_score'], 1) . "%";
            $recommendation = $dup['recommendation'];
            $type = $dup['type'] === 'within_raw' ? 'Internal' : 'Existing';
            
            $md .= "| " . ($i + 1) . " | {$type} | {$q1_preview} | {$q2_preview} | {$similarity} | {$recommendation} |\n";
        }
        
        if (count($report['duplicates']) > 50) {
            $md .= "\n*... and " . (count($report['duplicates']) - 50) . " more. See JSON report for full details.*\n";
        }
        
        file_put_contents($outputFile, $md);
    }
    
    /**
     * Print summary to console
     */
    public function printSummary(): void
    {
        $report = $this->generateReport();
        
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "DEDUPLICATION REPORT\n";
        echo str_repeat('=', 70) . "\n\n";
        
        echo "Original Questions:         " . $report['summary']['original_count'] . "\n";
        echo "Unique Questions:           " . $report['summary']['unique_count'] . "\n";
        echo "Duplicate Groups:           " . $report['summary']['duplicate_groups'] . "\n";
        echo "Total Duplicates Found:     " . $report['summary']['total_duplicates_found'] . "\n";
        echo "  - Within Raw Questions:   " . $report['summary']['duplicates_within_raw'] . "\n";
        echo "  - Against Existing:       " . $report['summary']['duplicates_against_existing'] . "\n";
        
        if ($report['summary']['existing_count'] > 0) {
            echo "Existing Questions Checked: " . $report['summary']['existing_count'] . "\n";
        }
        
        echo "\nSimilarity Distribution:\n";
        foreach ($report['statistics']['similarity_distribution'] as $range => $count) {
            if ($count > 0) {
                echo sprintf("  %-10s: %d\n", $range, $count);
            }
        }
        
        echo "\nMerge Recommendations:\n";
        echo "  High Confidence Merges:   " . count($report['recommendations']['high_confidence_merges']) . "\n";
        echo "  Review Required:          " . count($report['recommendations']['review_required']) . "\n";
        echo "  Already Exists:           " . count($report['recommendations']['already_exists']) . "\n";
        
        echo "\nProcessing Time: " . round($report['metadata']['processing_time'], 2) . "s\n";
        
        echo "\n" . str_repeat('=', 70) . "\n\n";
    }
    
    /**
     * Run deduplication process
     */
    public function run(bool $checkExisting = false): void
    {
        $startTime = microtime(true);
        
        echo "Finding duplicates within raw questions...\n";
        $this->findDuplicatesWithinRaw();
        echo "Found {$this->statistics['duplicates_within_raw']} duplicates within raw questions.\n";
        
        if ($checkExisting) {
            echo "\nLoading existing questions from interview bank...\n";
            $this->loadExistingQuestions();
            echo "Loaded {$this->statistics['existing_questions_count']} existing questions.\n";
            
            echo "\nFinding duplicates against existing questions...\n";
            $this->findDuplicatesAgainstExisting();
            echo "Found {$this->statistics['duplicates_against_existing']} duplicates against existing questions.\n";
        }
        
        $this->statistics['processing_time'] = microtime(true) - $startTime;
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $scriptName = basename(__FILE__);
    
    if ($argc < 2) {
        echo "Usage: php {$scriptName} <raw-questions-file> [options]\n\n";
        echo "Arguments:\n";
        echo "  raw-questions-file         Path to raw questions file\n\n";
        echo "Options:\n";
        echo "  --threshold=<number>       Similarity threshold (0-100, default: 85)\n";
        echo "  --output=<file>            Output JSON report file (default: deduplication-report.json)\n";
        echo "  --markdown=<file>          Generate markdown report\n";
        echo "  --check-existing           Check against existing interview-bank questions\n";
        echo "  --summary-only             Only show summary (no file output)\n\n";
        echo "Examples:\n";
        echo "  php {$scriptName} raw-questions.txt\n";
        echo "  php {$scriptName} questions.md --threshold=90 --markdown=report.md\n";
        echo "  php {$scriptName} new-questions.txt --check-existing --output=dupes.json\n";
        exit(1);
    }
    
    $rawFile = $argv[1];
    $threshold = 85.0;
    $outputFile = 'deduplication-report.json';
    $markdownFile = null;
    $checkExisting = false;
    $summaryOnly = false;
    
    // Parse options
    for ($i = 2; $i < $argc; $i++) {
        if (strpos($argv[$i], '--threshold=') === 0) {
            $threshold = (float)substr($argv[$i], 12);
        } elseif (strpos($argv[$i], '--output=') === 0) {
            $outputFile = substr($argv[$i], 9);
        } elseif (strpos($argv[$i], '--markdown=') === 0) {
            $markdownFile = substr($argv[$i], 11);
        } elseif ($argv[$i] === '--check-existing') {
            $checkExisting = true;
        } elseif ($argv[$i] === '--summary-only') {
            $summaryOnly = true;
        }
    }
    
    try {
        echo "Question Deduplication Script\n";
        echo "=============================\n\n";
        echo "Raw questions file: {$rawFile}\n";
        echo "Similarity threshold: {$threshold}%\n";
        echo "Check existing: " . ($checkExisting ? 'Yes' : 'No') . "\n\n";
        
        $deduplicator = new QuestionDeduplicator($threshold);
        
        echo "Loading raw questions...\n";
        $deduplicator->loadRawQuestions($rawFile);
        
        $deduplicator->run($checkExisting);
        
        $deduplicator->printSummary();
        
        if (!$summaryOnly) {
            $deduplicator->exportToJson($outputFile);
            echo "JSON report saved to: {$outputFile}\n";
            
            if ($markdownFile) {
                $deduplicator->exportToMarkdown($markdownFile);
                echo "Markdown report saved to: {$markdownFile}\n";
            }
        }
        
        echo "\nDeduplication completed successfully!\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
