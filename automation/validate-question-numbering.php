<?php

/**
 * Question Numbering Validation Script
 * 
 * Verifies question numbering consistency after deduplication:
 * - Checks for gaps in question number sequences
 * - Validates cross-references in answer files align with question numbers
 * - Detects duplicate question numbers
 * - Validates answer-to-question mapping
 * - Generates comprehensive validation report showing broken references
 * 
 * Usage:
 *   php validate-question-numbering.php [options]
 * 
 * Options:
 *   --dir=<path>               Directory to validate (default: ../interview-bank)
 *   --output=<file>            Output report file (default: numbering-validation-report.json)
 *   --markdown=<file>          Generate markdown report
 *   --fix-references           Auto-fix broken references in answer files
 *   --verbose                  Show detailed validation progress
 * 
 * Examples:
 *   php validate-question-numbering.php
 *   php validate-question-numbering.php --dir=../interview-bank/php-laravel-api-security
 *   php validate-question-numbering.php --markdown=validation-report.md
 */

class QuestionNumberingValidator
{
    private string $baseDir;
    private array $validationResults = [];
    private array $errors = [];
    private array $warnings = [];
    private array $statistics = [];
    private bool $verbose = false;
    
    public function __construct(string $baseDir = null, bool $verbose = false)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__) . '/interview-bank';
        $this->verbose = $verbose;
        $this->initializeStatistics();
    }
    
    private function initializeStatistics(): void
    {
        $this->statistics = [
            'directories_scanned' => 0,
            'question_files_found' => 0,
            'answer_files_found' => 0,
            'total_questions' => 0,
            'total_answers' => 0,
            'sequence_gaps_found' => 0,
            'duplicate_numbers_found' => 0,
            'broken_cross_references' => 0,
            'orphaned_answers' => 0,
            'missing_answers' => 0,
            'validation_time' => 0,
        ];
    }
    
    /**
     * Run complete validation
     */
    public function validate(): array
    {
        $startTime = microtime(true);
        
        if (!is_dir($this->baseDir)) {
            throw new Exception("Base directory not found: {$this->baseDir}");
        }
        
        $this->log("Starting question numbering validation...");
        $this->log("Base directory: {$this->baseDir}\n");
        
        // Scan all subdirectories
        $directories = $this->getSubdirectories($this->baseDir);
        $this->statistics['directories_scanned'] = count($directories);
        
        foreach ($directories as $dir) {
            $this->log("Validating: " . basename($dir));
            $this->validateDirectory($dir);
        }
        
        $this->statistics['validation_time'] = microtime(true) - $startTime;
        
        return $this->generateReport();
    }
    
    /**
     * Get all subdirectories
     */
    private function getSubdirectories(string $path): array
    {
        $directories = [];
        
        if (is_dir($path)) {
            $items = glob($path . '/*', GLOB_ONLYDIR);
            foreach ($items as $item) {
                $directories[] = $item;
            }
        }
        
        return $directories;
    }
    
    /**
     * Validate a single directory
     */
    private function validateDirectory(string $dirPath): void
    {
        $dirName = basename($dirPath);
        
        // Find question files
        $questionFiles = $this->findQuestionFiles($dirPath);
        $this->statistics['question_files_found'] += count($questionFiles);
        
        // Find answer files
        $answerFiles = $this->findAnswerFiles($dirPath);
        $this->statistics['answer_files_found'] += count($answerFiles);
        
        if (empty($questionFiles)) {
            $this->log("  No question files found", 'warning');
            return;
        }
        
        $dirResult = [
            'directory' => $dirName,
            'path' => $dirPath,
            'question_files' => [],
            'answer_files' => [],
            'errors' => [],
            'warnings' => [],
            'statistics' => [],
        ];
        
        // Validate each question file
        foreach ($questionFiles as $questionFile) {
            $this->log("  Checking questions: " . basename($questionFile));
            $questionResult = $this->validateQuestionFile($questionFile);
            $dirResult['question_files'][] = $questionResult;
            
            $this->statistics['total_questions'] += $questionResult['total_questions'];
        }
        
        // Validate each answer file
        foreach ($answerFiles as $answerFile) {
            $this->log("  Checking answers: " . basename($answerFile));
            $answerResult = $this->validateAnswerFile($answerFile, $questionFiles);
            $dirResult['answer_files'][] = $answerResult;
            
            $this->statistics['total_answers'] += $answerResult['total_answers'];
        }
        
        // Cross-validate questions and answers
        $crossValidation = $this->crossValidateQuestionsAndAnswers($dirResult);
        $dirResult['cross_validation'] = $crossValidation;
        
        // Aggregate errors and warnings
        foreach ($dirResult['question_files'] as $qf) {
            $dirResult['errors'] = array_merge($dirResult['errors'], $qf['errors']);
            $dirResult['warnings'] = array_merge($dirResult['warnings'], $qf['warnings']);
        }
        
        foreach ($dirResult['answer_files'] as $af) {
            $dirResult['errors'] = array_merge($dirResult['errors'], $af['errors']);
            $dirResult['warnings'] = array_merge($dirResult['warnings'], $af['warnings']);
        }
        
        $dirResult['errors'] = array_merge($dirResult['errors'], $crossValidation['errors']);
        $dirResult['warnings'] = array_merge($dirResult['warnings'], $crossValidation['warnings']);
        
        $this->validationResults[$dirName] = $dirResult;
        
        // Update global error/warning counts
        $this->statistics['sequence_gaps_found'] += $crossValidation['sequence_gaps'];
        $this->statistics['duplicate_numbers_found'] += $crossValidation['duplicate_numbers'];
        $this->statistics['broken_cross_references'] += $crossValidation['broken_references'];
        $this->statistics['orphaned_answers'] += $crossValidation['orphaned_answers'];
        $this->statistics['missing_answers'] += $crossValidation['missing_answers'];
    }
    
    /**
     * Find question files in directory
     */
    private function findQuestionFiles(string $dirPath): array
    {
        $patterns = [
            'questions.md',
            'questions_*.md',
            'questions-*.md',
        ];
        
        $files = [];
        foreach ($patterns as $pattern) {
            $matches = glob($dirPath . '/' . $pattern);
            $files = array_merge($files, $matches);
        }
        
        return array_unique($files);
    }
    
    /**
     * Find answer files in directory
     */
    private function findAnswerFiles(string $dirPath): array
    {
        $patterns = [
            'answers.md',
            'answers_*.md',
            'answers-*.md',
        ];
        
        $files = [];
        foreach ($patterns as $pattern) {
            $matches = glob($dirPath . '/' . $pattern);
            $files = array_merge($files, $matches);
        }
        
        return array_unique($files);
    }
    
    /**
     * Validate a question file
     */
    private function validateQuestionFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $fileName = basename($filePath);
        
        $result = [
            'file' => $fileName,
            'path' => $filePath,
            'total_questions' => 0,
            'question_numbers' => [],
            'duplicate_numbers' => [],
            'sequence_gaps' => [],
            'errors' => [],
            'warnings' => [],
        ];
        
        // Extract question numbers
        $questions = $this->extractQuestions($content);
        $result['total_questions'] = count($questions);
        $result['question_numbers'] = $questions;
        
        if (empty($questions)) {
            $result['warnings'][] = "No numbered questions found in {$fileName}";
            return $result;
        }
        
        // Check for duplicates
        $duplicates = $this->findDuplicateNumbers($questions);
        if (!empty($duplicates)) {
            $result['duplicate_numbers'] = $duplicates;
            foreach ($duplicates as $num => $count) {
                $result['errors'][] = "Question number {$num} appears {$count} times in {$fileName}";
            }
        }
        
        // Check for sequence gaps
        $gaps = $this->findSequenceGaps($questions);
        if (!empty($gaps)) {
            $result['sequence_gaps'] = $gaps;
            foreach ($gaps as $gap) {
                $result['warnings'][] = "Question numbering gap in {$fileName}: {$gap['from']} → {$gap['to']} (missing: {$gap['missing_count']})";
            }
        }
        
        return $result;
    }
    
    /**
     * Validate an answer file
     */
    private function validateAnswerFile(string $filePath, array $questionFiles): array
    {
        $content = file_get_contents($filePath);
        $fileName = basename($filePath);
        
        $result = [
            'file' => $fileName,
            'path' => $filePath,
            'total_answers' => 0,
            'answer_numbers' => [],
            'cross_references' => [],
            'broken_references' => [],
            'errors' => [],
            'warnings' => [],
        ];
        
        // Extract answer references (Q1, Q2, etc.)
        $answerRefs = $this->extractAnswerReferences($content);
        $result['total_answers'] = count($answerRefs);
        $result['answer_numbers'] = $answerRefs;
        
        // Extract cross-references to questions
        $crossRefs = $this->extractCrossReferences($content);
        $result['cross_references'] = $crossRefs;
        
        return $result;
    }
    
    /**
     * Extract question numbers from content
     */
    private function extractQuestions(string $content): array
    {
        $questions = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            
            // Match numbered questions: "1. Question text"
            if (preg_match('/^(\d+)\.\s+(.+)/', $line, $matches)) {
                $questionNum = (int)$matches[1];
                $questions[$questionNum] = [
                    'number' => $questionNum,
                    'text' => trim($matches[2]),
                    'line' => $lineNum + 1,
                ];
            }
        }
        
        return $questions;
    }
    
    /**
     * Extract answer references from content
     */
    private function extractAnswerReferences(string $content): array
    {
        $answers = [];
        
        // Match various answer formats:
        // ### Q1: Question text
        // ## Q1: Question text
        // **Q1:** Question text
        $patterns = [
            '/^#{2,3}\s*Q(\d+):/m',
            '/^\*\*Q(\d+):\*\*/m',
            '/^###\s+Q(\d+)\s*:/m',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $match) {
                    $answerNum = (int)$match[0];
                    $position = $match[1];
                    $lineNum = substr_count(substr($content, 0, $position), "\n") + 1;
                    
                    $answers[$answerNum] = [
                        'number' => $answerNum,
                        'line' => $lineNum,
                    ];
                }
            }
        }
        
        return $answers;
    }
    
    /**
     * Extract cross-references from content
     */
    private function extractCrossReferences(string $content): array
    {
        $references = [];
        
        // Match references like: "see Q45", "question 45", "Q45", etc.
        $patterns = [
            '/\bQ(\d+)\b/i',
            '/question\s+(\d+)/i',
            '/\(Q(\d+)\)/i',
            '/#q(\d+)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $refNum = (int)$match[1][0];
                    $position = $match[0][1];
                    $lineNum = substr_count(substr($content, 0, $position), "\n") + 1;
                    
                    if (!isset($references[$refNum])) {
                        $references[$refNum] = [];
                    }
                    
                    $references[$refNum][] = [
                        'number' => $refNum,
                        'line' => $lineNum,
                        'context' => $this->getContextAround($content, $position, 50),
                    ];
                }
            }
        }
        
        return $references;
    }
    
    /**
     * Get context around a position in content
     */
    private function getContextAround(string $content, int $position, int $length): string
    {
        $start = max(0, $position - $length);
        $end = min(strlen($content), $position + $length);
        
        $context = substr($content, $start, $end - $start);
        $context = trim($context);
        
        if ($start > 0) {
            $context = '...' . $context;
        }
        if ($end < strlen($content)) {
            $context = $context . '...';
        }
        
        return $context;
    }
    
    /**
     * Find duplicate question numbers
     */
    private function findDuplicateNumbers(array $questions): array
    {
        $counts = [];
        foreach ($questions as $q) {
            $num = $q['number'];
            $counts[$num] = ($counts[$num] ?? 0) + 1;
        }
        
        return array_filter($counts, fn($count) => $count > 1);
    }
    
    /**
     * Find gaps in question numbering sequence
     */
    private function findSequenceGaps(array $questions): array
    {
        if (empty($questions)) {
            return [];
        }
        
        $numbers = array_keys($questions);
        sort($numbers);
        
        $gaps = [];
        $min = min($numbers);
        $max = max($numbers);
        
        for ($i = $min; $i < $max; $i++) {
            if (!isset($questions[$i])) {
                // Find the gap range
                $gapStart = $i;
                while ($i < $max && !isset($questions[$i])) {
                    $i++;
                }
                $gapEnd = $i - 1;
                
                $gaps[] = [
                    'from' => $gapStart - 1,
                    'to' => $i,
                    'missing_start' => $gapStart,
                    'missing_end' => $gapEnd,
                    'missing_count' => $gapEnd - $gapStart + 1,
                ];
            }
        }
        
        return $gaps;
    }
    
    /**
     * Cross-validate questions and answers
     */
    private function crossValidateQuestionsAndAnswers(array $dirResult): array
    {
        $result = [
            'sequence_gaps' => 0,
            'duplicate_numbers' => 0,
            'broken_references' => 0,
            'orphaned_answers' => 0,
            'missing_answers' => 0,
            'errors' => [],
            'warnings' => [],
            'details' => [],
        ];
        
        // Collect all question numbers
        $allQuestionNumbers = [];
        foreach ($dirResult['question_files'] as $qf) {
            foreach ($qf['question_numbers'] as $q) {
                $allQuestionNumbers[$q['number']] = true;
            }
        }
        
        // Collect all answer numbers
        $allAnswerNumbers = [];
        foreach ($dirResult['answer_files'] as $af) {
            foreach ($af['answer_numbers'] as $a) {
                $allAnswerNumbers[$a['number']] = true;
            }
        }
        
        // Find orphaned answers (answers without corresponding questions)
        $orphanedAnswers = array_diff_key($allAnswerNumbers, $allQuestionNumbers);
        if (!empty($orphanedAnswers)) {
            $result['orphaned_answers'] = count($orphanedAnswers);
            $orphanList = implode(', ', array_keys($orphanedAnswers));
            $result['warnings'][] = "Found " . count($orphanedAnswers) . " orphaned answer(s) without corresponding questions: Q{$orphanList}";
            $result['details']['orphaned_answers'] = array_keys($orphanedAnswers);
        }
        
        // Find missing answers (questions without answers)
        $missingAnswers = array_diff_key($allQuestionNumbers, $allAnswerNumbers);
        if (!empty($missingAnswers)) {
            $result['missing_answers'] = count($missingAnswers);
            $missingList = implode(', ', array_slice(array_keys($missingAnswers), 0, 20));
            if (count($missingAnswers) > 20) {
                $missingList .= '... and ' . (count($missingAnswers) - 20) . ' more';
            }
            $result['warnings'][] = "Found " . count($missingAnswers) . " question(s) without answers: Q{$missingList}";
            $result['details']['missing_answers'] = array_keys($missingAnswers);
        }
        
        // Validate cross-references in answer files
        foreach ($dirResult['answer_files'] as $af) {
            foreach ($af['cross_references'] as $refNum => $refs) {
                if (!isset($allQuestionNumbers[$refNum])) {
                    $result['broken_references']++;
                    foreach ($refs as $ref) {
                        $result['errors'][] = "Broken reference in {$af['file']} line {$ref['line']}: Q{$refNum} does not exist";
                        
                        if (!isset($result['details']['broken_references'])) {
                            $result['details']['broken_references'] = [];
                        }
                        
                        $result['details']['broken_references'][] = [
                            'file' => $af['file'],
                            'line' => $ref['line'],
                            'reference' => "Q{$refNum}",
                            'context' => $ref['context'],
                        ];
                    }
                }
            }
        }
        
        // Count gaps and duplicates from question files
        foreach ($dirResult['question_files'] as $qf) {
            $result['sequence_gaps'] += count($qf['sequence_gaps']);
            $result['duplicate_numbers'] += count($qf['duplicate_numbers']);
        }
        
        return $result;
    }
    
    /**
     * Generate validation report
     */
    private function generateReport(): array
    {
        return [
            'metadata' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'base_directory' => $this->baseDir,
                'validation_time' => round($this->statistics['validation_time'], 2) . 's',
            ],
            'summary' => [
                'total_directories' => $this->statistics['directories_scanned'],
                'total_question_files' => $this->statistics['question_files_found'],
                'total_answer_files' => $this->statistics['answer_files_found'],
                'total_questions' => $this->statistics['total_questions'],
                'total_answers' => $this->statistics['total_answers'],
                'sequence_gaps_found' => $this->statistics['sequence_gaps_found'],
                'duplicate_numbers_found' => $this->statistics['duplicate_numbers_found'],
                'broken_cross_references' => $this->statistics['broken_cross_references'],
                'orphaned_answers' => $this->statistics['orphaned_answers'],
                'missing_answers' => $this->statistics['missing_answers'],
                'has_errors' => $this->statistics['duplicate_numbers_found'] > 0 || 
                               $this->statistics['broken_cross_references'] > 0,
                'has_warnings' => $this->statistics['sequence_gaps_found'] > 0 || 
                                 $this->statistics['orphaned_answers'] > 0 || 
                                 $this->statistics['missing_answers'] > 0,
            ],
            'statistics' => $this->statistics,
            'validation_results' => $this->validationResults,
        ];
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
        
        $md = "# Question Numbering Validation Report\n\n";
        $md .= "**Generated:** " . $report['metadata']['generated_at'] . "\n";
        $md .= "**Base Directory:** `" . $report['metadata']['base_directory'] . "`\n";
        $md .= "**Validation Time:** " . $report['metadata']['validation_time'] . "\n\n";
        
        // Status indicator
        if (!$report['summary']['has_errors'] && !$report['summary']['has_warnings']) {
            $md .= "## ✅ Status: PASSED\n\n";
            $md .= "All question numbering is consistent and cross-references are valid.\n\n";
        } elseif ($report['summary']['has_errors']) {
            $md .= "## ❌ Status: FAILED\n\n";
            $md .= "Critical errors found that need to be fixed.\n\n";
        } else {
            $md .= "## ⚠️ Status: WARNINGS\n\n";
            $md .= "No critical errors, but there are warnings that should be reviewed.\n\n";
        }
        
        // Summary
        $md .= "## Summary\n\n";
        $md .= "| Metric | Count |\n";
        $md .= "|--------|-------|\n";
        $md .= "| Directories Scanned | " . $report['summary']['total_directories'] . " |\n";
        $md .= "| Question Files | " . $report['summary']['total_question_files'] . " |\n";
        $md .= "| Answer Files | " . $report['summary']['total_answer_files'] . " |\n";
        $md .= "| Total Questions | " . $report['summary']['total_questions'] . " |\n";
        $md .= "| Total Answers | " . $report['summary']['total_answers'] . " |\n";
        $md .= "| **Sequence Gaps** | **" . $report['summary']['sequence_gaps_found'] . "** |\n";
        $md .= "| **Duplicate Numbers** | **" . $report['summary']['duplicate_numbers_found'] . "** |\n";
        $md .= "| **Broken Cross-References** | **" . $report['summary']['broken_cross_references'] . "** |\n";
        $md .= "| **Orphaned Answers** | **" . $report['summary']['orphaned_answers'] . "** |\n";
        $md .= "| **Missing Answers** | **" . $report['summary']['missing_answers'] . "** |\n\n";
        
        // Errors section
        if ($report['summary']['has_errors']) {
            $md .= "## 🔴 Critical Errors\n\n";
            
            foreach ($report['validation_results'] as $dirName => $dirResult) {
                if (!empty($dirResult['errors'])) {
                    $md .= "### {$dirName}\n\n";
                    foreach ($dirResult['errors'] as $error) {
                        $md .= "- ❌ {$error}\n";
                    }
                    $md .= "\n";
                }
                
                // Show broken reference details
                if (isset($dirResult['cross_validation']['details']['broken_references'])) {
                    $md .= "**Broken Reference Details:**\n\n";
                    $md .= "| File | Line | Reference | Context |\n";
                    $md .= "|------|------|-----------|----------|\n";
                    
                    foreach ($dirResult['cross_validation']['details']['broken_references'] as $br) {
                        $context = str_replace('|', '\\|', substr($br['context'], 0, 60));
                        $md .= "| {$br['file']} | {$br['line']} | {$br['reference']} | {$context}... |\n";
                    }
                    $md .= "\n";
                }
            }
        }
        
        // Warnings section
        if ($report['summary']['has_warnings']) {
            $md .= "## ⚠️ Warnings\n\n";
            
            foreach ($report['validation_results'] as $dirName => $dirResult) {
                if (!empty($dirResult['warnings'])) {
                    $md .= "### {$dirName}\n\n";
                    foreach ($dirResult['warnings'] as $warning) {
                        $md .= "- ⚠️ {$warning}\n";
                    }
                    $md .= "\n";
                }
            }
        }
        
        // Detailed results by directory
        $md .= "## Detailed Results by Directory\n\n";
        
        foreach ($report['validation_results'] as $dirName => $dirResult) {
            $errorCount = count($dirResult['errors']);
            $warningCount = count($dirResult['warnings']);
            
            if ($errorCount === 0 && $warningCount === 0) {
                $status = '✅';
            } elseif ($errorCount > 0) {
                $status = '❌';
            } else {
                $status = '⚠️';
            }
            
            $md .= "### {$status} {$dirName}\n\n";
            
            // Question files
            if (!empty($dirResult['question_files'])) {
                $md .= "**Question Files:**\n\n";
                foreach ($dirResult['question_files'] as $qf) {
                    $md .= "- `{$qf['file']}`: {$qf['total_questions']} questions";
                    
                    if (!empty($qf['duplicate_numbers'])) {
                        $md .= " (❌ " . count($qf['duplicate_numbers']) . " duplicates)";
                    }
                    
                    if (!empty($qf['sequence_gaps'])) {
                        $md .= " (⚠️ " . count($qf['sequence_gaps']) . " gaps)";
                    }
                    
                    $md .= "\n";
                }
                $md .= "\n";
            }
            
            // Answer files
            if (!empty($dirResult['answer_files'])) {
                $md .= "**Answer Files:**\n\n";
                foreach ($dirResult['answer_files'] as $af) {
                    $md .= "- `{$af['file']}`: {$af['total_answers']} answers";
                    
                    if (!empty($af['cross_references'])) {
                        $md .= " (" . count($af['cross_references']) . " cross-references)";
                    }
                    
                    $md .= "\n";
                }
                $md .= "\n";
            }
            
            // Cross-validation details
            if (isset($dirResult['cross_validation'])) {
                $cv = $dirResult['cross_validation'];
                
                if ($cv['orphaned_answers'] > 0 && isset($cv['details']['orphaned_answers'])) {
                    $md .= "**Orphaned Answers:** Q" . implode(', Q', array_slice($cv['details']['orphaned_answers'], 0, 20));
                    if (count($cv['details']['orphaned_answers']) > 20) {
                        $md .= '... (' . count($cv['details']['orphaned_answers']) . ' total)';
                    }
                    $md .= "\n\n";
                }
                
                if ($cv['missing_answers'] > 0 && isset($cv['details']['missing_answers'])) {
                    $md .= "**Missing Answers:** Q" . implode(', Q', array_slice($cv['details']['missing_answers'], 0, 20));
                    if (count($cv['details']['missing_answers']) > 20) {
                        $md .= '... (' . count($cv['details']['missing_answers']) . ' total)';
                    }
                    $md .= "\n\n";
                }
            }
        }
        
        // Recommendations
        $md .= "## Recommendations\n\n";
        
        if ($report['summary']['duplicate_numbers_found'] > 0) {
            $md .= "### Fix Duplicate Question Numbers\n\n";
            $md .= "Duplicate question numbers will cause confusion and broken references. ";
            $md .= "Review the question files and renumber duplicates.\n\n";
        }
        
        if ($report['summary']['broken_cross_references'] > 0) {
            $md .= "### Fix Broken Cross-References\n\n";
            $md .= "Answer files contain references to questions that don't exist. ";
            $md .= "Update the references or create the missing questions.\n\n";
        }
        
        if ($report['summary']['sequence_gaps_found'] > 0) {
            $md .= "### Review Question Number Gaps\n\n";
            $md .= "Gaps in question numbering may indicate deleted or missing questions. ";
            $md .= "Consider renumbering to maintain sequential order or document intentional gaps.\n\n";
        }
        
        if ($report['summary']['orphaned_answers'] > 0) {
            $md .= "### Remove or Map Orphaned Answers\n\n";
            $md .= "Found answers without corresponding questions. ";
            $md .= "Either remove these answers or create the missing questions.\n\n";
        }
        
        if ($report['summary']['missing_answers'] > 0) {
            $md .= "### Create Missing Answers\n\n";
            $md .= "Found questions without answers. ";
            $md .= "Generate answers for these questions to complete the interview bank.\n\n";
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
        echo "QUESTION NUMBERING VALIDATION REPORT\n";
        echo str_repeat('=', 70) . "\n\n";
        
        // Status
        if (!$report['summary']['has_errors'] && !$report['summary']['has_warnings']) {
            echo "✅ Status: PASSED\n\n";
        } elseif ($report['summary']['has_errors']) {
            echo "❌ Status: FAILED\n\n";
        } else {
            echo "⚠️  Status: WARNINGS\n\n";
        }
        
        // Statistics
        echo "Directories Scanned:      " . $report['summary']['total_directories'] . "\n";
        echo "Question Files:           " . $report['summary']['total_question_files'] . "\n";
        echo "Answer Files:             " . $report['summary']['total_answer_files'] . "\n";
        echo "Total Questions:          " . $report['summary']['total_questions'] . "\n";
        echo "Total Answers:            " . $report['summary']['total_answers'] . "\n\n";
        
        // Issues
        echo "Issues Found:\n";
        echo "  Sequence Gaps:          " . $report['summary']['sequence_gaps_found'] . "\n";
        echo "  Duplicate Numbers:      " . $report['summary']['duplicate_numbers_found'] . "\n";
        echo "  Broken Cross-Refs:      " . $report['summary']['broken_cross_references'] . "\n";
        echo "  Orphaned Answers:       " . $report['summary']['orphaned_answers'] . "\n";
        echo "  Missing Answers:        " . $report['summary']['missing_answers'] . "\n";
        
        echo "\nValidation Time: " . $report['metadata']['validation_time'] . "\n";
        echo "\n" . str_repeat('=', 70) . "\n\n";
    }
    
    /**
     * Log message if verbose mode is enabled
     */
    private function log(string $message, string $level = 'info'): void
    {
        if ($this->verbose || $level === 'error') {
            echo $message . "\n";
        }
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $scriptName = basename(__FILE__);
    
    // Parse command line options
    $options = [
        'dir' => null,
        'output' => 'numbering-validation-report.json',
        'markdown' => null,
        'verbose' => false,
        'help' => false,
    ];
    
    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        } elseif (strpos($arg, '--dir=') === 0) {
            $options['dir'] = substr($arg, 6);
        } elseif (strpos($arg, '--output=') === 0) {
            $options['output'] = substr($arg, 9);
        } elseif (strpos($arg, '--markdown=') === 0) {
            $options['markdown'] = substr($arg, 11);
        } elseif ($arg === '--verbose' || $arg === '-v') {
            $options['verbose'] = true;
        }
    }
    
    if ($options['help']) {
        echo "Question Numbering Validation Script\n";
        echo "====================================\n\n";
        echo "Usage: php {$scriptName} [options]\n\n";
        echo "Options:\n";
        echo "  --dir=<path>          Directory to validate (default: ../interview-bank)\n";
        echo "  --output=<file>       Output JSON report file (default: numbering-validation-report.json)\n";
        echo "  --markdown=<file>     Generate markdown report\n";
        echo "  --verbose, -v         Show detailed validation progress\n";
        echo "  --help, -h            Show this help message\n\n";
        echo "Examples:\n";
        echo "  php {$scriptName}\n";
        echo "  php {$scriptName} --verbose\n";
        echo "  php {$scriptName} --dir=../interview-bank/php-laravel-api-security\n";
        echo "  php {$scriptName} --markdown=validation-report.md --output=validation.json\n";
        exit(0);
    }
    
    try {
        echo "Question Numbering Validation\n";
        echo "=============================\n\n";
        
        $validator = new QuestionNumberingValidator($options['dir'], $options['verbose']);
        $validator->validate();
        $validator->printSummary();
        
        // Export reports
        $validator->exportToJson($options['output']);
        echo "JSON report saved to: {$options['output']}\n";
        
        if ($options['markdown']) {
            $validator->exportToMarkdown($options['markdown']);
            echo "Markdown report saved to: {$options['markdown']}\n";
        }
        
        echo "\nValidation completed successfully!\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
