#!/usr/bin/env php
<?php

/**
 * Question Deduplication Analyzer
 * 
 * Analyzes all question files in interview-bank/php-laravel-api-security/
 * to identify potential duplicate questions using Levenshtein distance algorithm.
 */

class QuestionDeduplicator
{
    private const SIMILARITY_THRESHOLD = 0.85;
    private const LEVENSHTEIN_THRESHOLD = 20;
    private array $questions = [];
    private array $duplicates = [];
    
    public function __construct(
        private string $directoryPath = 'interview-bank/php-laravel-api-security'
    ) {}
    
    public function analyze(): void
    {
        echo "Question Deduplication Analysis\n";
        echo str_repeat("=", 80) . "\n\n";
        
        $this->loadQuestions();
        $this->findDuplicates();
        $this->generateReport();
    }
    
    private function loadQuestions(): void
    {
        echo "Loading questions from {$this->directoryPath}...\n";
        
        $files = glob($this->directoryPath . '/*.md');
        
        foreach ($files as $file) {
            if (basename($file) === 'README.md' || 
                basename($file) === 'INDEX.md' ||
                strpos(basename($file), 'IMPLEMENTATION') !== false ||
                strpos(basename($file), 'answers') !== false) {
                continue;
            }
            
            $this->parseQuestionFile($file);
        }
        
        echo "Loaded " . count($this->questions) . " questions from " . count($files) . " files.\n\n";
    }
    
    private function parseQuestionFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/^(\d+)\.\s+(.+)$/', trim($line), $matches)) {
                $questionNumber = (int)$matches[1];
                $questionText = trim($matches[2]);
                
                $this->questions[] = [
                    'number' => $questionNumber,
                    'text' => $questionText,
                    'file' => basename($filePath),
                    'line' => $lineNumber + 1,
                    'normalized' => $this->normalizeQuestion($questionText)
                ];
            }
        }
    }
    
    private function normalizeQuestion(string $text): string
    {
        $normalized = strtolower($text);
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }
    
    private function findDuplicates(): void
    {
        echo "Analyzing questions for duplicates...\n";
        echo "Using Levenshtein distance threshold: " . self::LEVENSHTEIN_THRESHOLD . "\n";
        echo "Using similarity threshold: " . (self::SIMILARITY_THRESHOLD * 100) . "%\n\n";
        
        $totalQuestions = count($this->questions);
        
        for ($i = 0; $i < $totalQuestions - 1; $i++) {
            for ($j = $i + 1; $j < $totalQuestions; $j++) {
                $q1 = $this->questions[$i];
                $q2 = $this->questions[$j];
                
                if ($q1['number'] === $q2['number']) {
                    continue;
                }
                
                $similarity = $this->calculateSimilarity($q1['normalized'], $q2['normalized']);
                $levenshteinDistance = $this->calculateLevenshteinDistance(
                    $q1['normalized'], 
                    $q2['normalized']
                );
                
                if ($similarity >= self::SIMILARITY_THRESHOLD || 
                    $levenshteinDistance <= self::LEVENSHTEIN_THRESHOLD) {
                    
                    $this->duplicates[] = [
                        'question1' => $q1,
                        'question2' => $q2,
                        'similarity' => $similarity,
                        'levenshtein_distance' => $levenshteinDistance,
                        'match_score' => $this->calculateMatchScore($similarity, $levenshteinDistance)
                    ];
                }
            }
        }
        
        usort($this->duplicates, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });
        
        echo "Found " . count($this->duplicates) . " potential duplicate pairs.\n\n";
    }
    
    private function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }
        
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        
        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }
        
        similar_text($str1, $str2, $percent);
        return $percent / 100;
    }
    
    private function calculateLevenshteinDistance(string $str1, string $str2): int
    {
        $maxLength = 255;
        
        if (strlen($str1) > $maxLength) {
            $str1 = substr($str1, 0, $maxLength);
        }
        
        if (strlen($str2) > $maxLength) {
            $str2 = substr($str2, 0, $maxLength);
        }
        
        return levenshtein($str1, $str2);
    }
    
    private function calculateMatchScore(float $similarity, int $levenshteinDistance): float
    {
        $similarityScore = $similarity * 100;
        $distanceScore = max(0, 100 - $levenshteinDistance);
        
        return ($similarityScore * 0.7) + ($distanceScore * 0.3);
    }
    
    private function generateReport(): void
    {
        $reportFile = 'deduplication-report.txt';
        $htmlReportFile = 'deduplication-report.html';
        
        $this->generateTextReport($reportFile);
        $this->generateHtmlReport($htmlReportFile);
        
        echo "\nReports generated:\n";
        echo "- Text Report: $reportFile\n";
        echo "- HTML Report: $htmlReportFile\n";
    }
    
    private function generateTextReport(string $filename): void
    {
        $report = $this->buildTextReport();
        file_put_contents($filename, $report);
    }
    
    private function buildTextReport(): string
    {
        $report = str_repeat("=", 80) . "\n";
        $report .= "QUESTION DEDUPLICATION REPORT\n";
        $report .= "Directory: {$this->directoryPath}\n";
        $report .= "Analysis Date: " . date('Y-m-d H:i:s') . "\n";
        $report .= str_repeat("=", 80) . "\n\n";
        
        $report .= "SUMMARY\n";
        $report .= str_repeat("-", 80) . "\n";
        $report .= "Total Questions Analyzed: " . count($this->questions) . "\n";
        $report .= "Duplicate Pairs Found: " . count($this->duplicates) . "\n";
        $report .= "Similarity Threshold: " . (self::SIMILARITY_THRESHOLD * 100) . "%\n";
        $report .= "Levenshtein Distance Threshold: " . self::LEVENSHTEIN_THRESHOLD . "\n";
        $report .= "\n";
        
        if (empty($this->duplicates)) {
            $report .= "No duplicate questions found!\n";
            return $report;
        }
        
        $report .= str_repeat("=", 80) . "\n";
        $report .= "DUPLICATE QUESTIONS FOUND\n";
        $report .= str_repeat("=", 80) . "\n\n";
        
        foreach ($this->duplicates as $index => $duplicate) {
            $report .= "Duplicate Pair #" . ($index + 1) . "\n";
            $report .= str_repeat("-", 80) . "\n";
            $report .= "Match Score: " . number_format($duplicate['match_score'], 2) . "%\n";
            $report .= "Similarity: " . number_format($duplicate['similarity'] * 100, 2) . "%\n";
            $report .= "Levenshtein Distance: " . $duplicate['levenshtein_distance'] . "\n\n";
            
            $report .= "Question #" . $duplicate['question1']['number'] . ":\n";
            $report .= "  File: " . $duplicate['question1']['file'] . " (Line " . $duplicate['question1']['line'] . ")\n";
            $report .= "  Text: " . $duplicate['question1']['text'] . "\n\n";
            
            $report .= "Question #" . $duplicate['question2']['number'] . ":\n";
            $report .= "  File: " . $duplicate['question2']['file'] . " (Line " . $duplicate['question2']['line'] . ")\n";
            $report .= "  Text: " . $duplicate['question2']['text'] . "\n\n";
            
            $report .= str_repeat("-", 80) . "\n\n";
        }
        
        $report .= $this->generateStatistics();
        
        return $report;
    }
    
    private function generateStatistics(): string
    {
        if (empty($this->duplicates)) {
            return "";
        }
        
        $stats = str_repeat("=", 80) . "\n";
        $stats .= "STATISTICS\n";
        $stats .= str_repeat("=", 80) . "\n\n";
        
        $questionNumbers = [];
        foreach ($this->duplicates as $duplicate) {
            $questionNumbers[] = $duplicate['question1']['number'];
            $questionNumbers[] = $duplicate['question2']['number'];
        }
        
        $uniqueQuestions = array_unique($questionNumbers);
        sort($uniqueQuestions);
        
        $stats .= "Questions with duplicates: " . count($uniqueQuestions) . "\n";
        $stats .= "Question numbers: " . implode(', ', $uniqueQuestions) . "\n\n";
        
        $highSimilarity = array_filter($this->duplicates, function($dup) {
            return $dup['similarity'] >= 0.95;
        });
        
        $stats .= "High similarity matches (>95%): " . count($highSimilarity) . "\n";
        
        $mediumSimilarity = array_filter($this->duplicates, function($dup) {
            return $dup['similarity'] >= 0.85 && $dup['similarity'] < 0.95;
        });
        
        $stats .= "Medium similarity matches (85-95%): " . count($mediumSimilarity) . "\n";
        
        $lowDistanceMatches = array_filter($this->duplicates, function($dup) {
            return $dup['levenshtein_distance'] <= 10;
        });
        
        $stats .= "Low distance matches (≤10): " . count($lowDistanceMatches) . "\n";
        
        return $stats;
    }
    
    private function generateHtmlReport(string $filename): void
    {
        $html = $this->buildHtmlReport();
        file_put_contents($filename, $html);
    }
    
    private function buildHtmlReport(): string
    {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Deduplication Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        h2 {
            color: #34495e;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 5px;
        }
        
        .summary {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        
        .summary-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .summary-value {
            color: #3498db;
            font-weight: 600;
        }
        
        .duplicate-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fafafa;
        }
        
        .duplicate-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 5px 5px 0 0;
            margin: -20px -20px 15px -20px;
            font-weight: 600;
        }
        
        .metrics {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .metric {
            flex: 1;
            min-width: 150px;
            background: white;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        
        .metric-label {
            font-size: 0.85em;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        
        .metric-value {
            font-size: 1.3em;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .question-box {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .question-number {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 3px 10px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 0.9em;
            margin-right: 10px;
        }
        
        .question-meta {
            font-size: 0.85em;
            color: #7f8c8d;
            margin-top: 8px;
        }
        
        .question-text {
            margin-top: 10px;
            color: #2c3e50;
            line-height: 1.5;
        }
        
        .statistics {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #4caf50;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .stat-value {
            font-size: 1.5em;
            font-weight: 700;
            color: #2c3e50;
            margin-top: 5px;
        }
        
        .no-duplicates {
            text-align: center;
            padding: 40px;
            background: #d4edda;
            border: 2px solid #c3e6cb;
            border-radius: 5px;
            color: #155724;
            font-size: 1.2em;
        }
        
        .score-high { border-left-color: #e74c3c; }
        .score-medium { border-left-color: #f39c12; }
        .score-low { border-left-color: #3498db; }
        
        @media print {
            body { background: white; }
            .container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Question Deduplication Report</h1>
        
        <div class="summary">
            <div class="summary-item">
                <span class="summary-label">Analysis Date:</span>
                <span class="summary-value">' . date('Y-m-d H:i:s') . '</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Directory:</span>
                <span class="summary-value">' . htmlspecialchars($this->directoryPath) . '</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Questions Analyzed:</span>
                <span class="summary-value">' . count($this->questions) . '</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Duplicate Pairs Found:</span>
                <span class="summary-value">' . count($this->duplicates) . '</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Similarity Threshold:</span>
                <span class="summary-value">' . (self::SIMILARITY_THRESHOLD * 100) . '%</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Levenshtein Distance Threshold:</span>
                <span class="summary-value">' . self::LEVENSHTEIN_THRESHOLD . '</span>
            </div>
        </div>';
        
        if (empty($this->duplicates)) {
            $html .= '<div class="no-duplicates">✅ No duplicate questions found!</div>';
        } else {
            $html .= '<h2>Duplicate Questions Found</h2>';
            
            foreach ($this->duplicates as $index => $duplicate) {
                $scoreClass = 'score-low';
                if ($duplicate['match_score'] >= 90) {
                    $scoreClass = 'score-high';
                } elseif ($duplicate['match_score'] >= 80) {
                    $scoreClass = 'score-medium';
                }
                
                $html .= '<div class="duplicate-card">
                    <div class="duplicate-header">
                        Duplicate Pair #' . ($index + 1) . '
                    </div>
                    
                    <div class="metrics">
                        <div class="metric ' . $scoreClass . '">
                            <div class="metric-label">Match Score</div>
                            <div class="metric-value">' . number_format($duplicate['match_score'], 2) . '%</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Similarity</div>
                            <div class="metric-value">' . number_format($duplicate['similarity'] * 100, 2) . '%</div>
                        </div>
                        <div class="metric">
                            <div class="metric-label">Levenshtein Distance</div>
                            <div class="metric-value">' . $duplicate['levenshtein_distance'] . '</div>
                        </div>
                    </div>
                    
                    <div class="question-box">
                        <div>
                            <span class="question-number">Question #' . $duplicate['question1']['number'] . '</span>
                        </div>
                        <div class="question-text">' . htmlspecialchars($duplicate['question1']['text']) . '</div>
                        <div class="question-meta">
                            📁 ' . htmlspecialchars($duplicate['question1']['file']) . ' (Line ' . $duplicate['question1']['line'] . ')
                        </div>
                    </div>
                    
                    <div class="question-box">
                        <div>
                            <span class="question-number">Question #' . $duplicate['question2']['number'] . '</span>
                        </div>
                        <div class="question-text">' . htmlspecialchars($duplicate['question2']['text']) . '</div>
                        <div class="question-meta">
                            📁 ' . htmlspecialchars($duplicate['question2']['file']) . ' (Line ' . $duplicate['question2']['line'] . ')
                        </div>
                    </div>
                </div>';
            }
            
            $html .= $this->generateHtmlStatistics();
        }
        
        $html .= '
    </div>
</body>
</html>';
        
        return $html;
    }
    
    private function generateHtmlStatistics(): string
    {
        if (empty($this->duplicates)) {
            return "";
        }
        
        $questionNumbers = [];
        foreach ($this->duplicates as $duplicate) {
            $questionNumbers[] = $duplicate['question1']['number'];
            $questionNumbers[] = $duplicate['question2']['number'];
        }
        
        $uniqueQuestions = array_unique($questionNumbers);
        sort($uniqueQuestions);
        
        $highSimilarity = count(array_filter($this->duplicates, function($dup) {
            return $dup['similarity'] >= 0.95;
        }));
        
        $mediumSimilarity = count(array_filter($this->duplicates, function($dup) {
            return $dup['similarity'] >= 0.85 && $dup['similarity'] < 0.95;
        }));
        
        $lowDistanceMatches = count(array_filter($this->duplicates, function($dup) {
            return $dup['levenshtein_distance'] <= 10;
        }));
        
        $html = '<div class="statistics">
            <h2>📈 Statistics</h2>
            <div class="stat-grid">
                <div class="stat-item">
                    <div class="stat-label">Questions with Duplicates</div>
                    <div class="stat-value">' . count($uniqueQuestions) . '</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">High Similarity Matches (>95%)</div>
                    <div class="stat-value">' . $highSimilarity . '</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Medium Similarity (85-95%)</div>
                    <div class="stat-value">' . $mediumSimilarity . '</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Low Distance Matches (≤10)</div>
                    <div class="stat-value">' . $lowDistanceMatches . '</div>
                </div>
            </div>
            <div style="margin-top: 15px; padding: 10px; background: white; border-radius: 5px;">
                <strong>Question Numbers with Duplicates:</strong><br>
                ' . implode(', ', $uniqueQuestions) . '
            </div>
        </div>';
        
        return $html;
    }
}

if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

$deduplicator = new QuestionDeduplicator();
$deduplicator->analyze();

echo "\nAnalysis complete!\n";
