<?php

/**
 * Question Statistics Analyzer
 * 
 * Analyzes the integrated question bank and generates comprehensive statistics including:
 * - Total questions per category
 * - Difficulty distribution
 * - Answered vs unanswered counts
 * - Coverage percentage
 * - Duplicate detection results
 * - Topic frequency analysis
 * 
 * Output: pages/question-statistics-report.html with interactive charts
 */

namespace InterviewBank\Automation;

class QuestionStatisticsAnalyzer
{
    private string $baseDir;
    private array $categories = [];
    private array $statistics = [];
    private array $duplicates = [];
    private array $topicFrequency = [];
    
    public function __construct(string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__) . '/interview-bank';
    }
    
    public function analyze(): array
    {
        echo "Starting analysis of question bank...\n";
        
        $this->scanCategories();
        $this->analyzeQuestions();
        $this->detectDuplicates();
        $this->analyzeTopics();
        $this->calculateStatistics();
        
        echo "Analysis complete!\n";
        
        return $this->statistics;
    }
    
    private function scanCategories(): void
    {
        echo "Scanning categories...\n";
        
        $dirs = glob($this->baseDir . '/*', GLOB_ONLYDIR);
        
        foreach ($dirs as $dir) {
            $category = basename($dir);
            $this->categories[$category] = [
                'path' => $dir,
                'name' => $this->formatCategoryName($category),
                'question_files' => [],
                'answer_files' => [],
            ];
            
            // Find question files
            $questionFiles = array_merge(
                glob($dir . '/questions*.md'),
                glob($dir . '/questions.md')
            );
            
            // Find answer files
            $answerFiles = array_merge(
                glob($dir . '/answers*.md'),
                glob($dir . '/answers.md')
            );
            
            $this->categories[$category]['question_files'] = $questionFiles;
            $this->categories[$category]['answer_files'] = $answerFiles;
        }
        
        echo "Found " . count($this->categories) . " categories\n";
    }
    
    private function analyzeQuestions(): void
    {
        echo "Analyzing questions...\n";
        
        foreach ($this->categories as $categoryKey => &$category) {
            $category['questions'] = [];
            $category['difficulty_distribution'] = [
                'basic' => 0,
                'intermediate' => 0,
                'advanced' => 0,
                'expert' => 0,
                'unknown' => 0
            ];
            $category['answered'] = 0;
            $category['unanswered'] = 0;
            
            foreach ($category['question_files'] as $file) {
                $questions = $this->extractQuestions($file);
                $category['questions'] = array_merge($category['questions'], $questions);
            }
            
            // Analyze each question
            foreach ($category['questions'] as &$question) {
                $difficulty = $this->detectDifficulty($question['text']);
                $question['difficulty'] = $difficulty;
                $category['difficulty_distribution'][$difficulty]++;
                
                // Check if answered
                $isAnswered = $this->isQuestionAnswered($question, $category['answer_files']);
                $question['answered'] = $isAnswered;
                
                if ($isAnswered) {
                    $category['answered']++;
                } else {
                    $category['unanswered']++;
                }
            }
            
            $category['total_questions'] = count($category['questions']);
            $category['coverage_percentage'] = $category['total_questions'] > 0 
                ? round(($category['answered'] / $category['total_questions']) * 100, 2)
                : 0;
        }
    }
    
    private function extractQuestions(string $file): array
    {
        $content = file_get_contents($file);
        $questions = [];
        
        // Pattern 1: Numbered questions (e.g., "1. Question text")
        preg_match_all('/^\d+\.\s+(.+?)$/m', $content, $numberedMatches);
        foreach ($numberedMatches[1] as $index => $questionText) {
            $questions[] = [
                'number' => $index + 1,
                'text' => trim($questionText),
                'file' => basename($file),
                'type' => 'numbered'
            ];
        }
        
        // Pattern 2: Questions with star ratings (e.g., "### ⭐ Question 1:")
        preg_match_all('/###\s+([⭐]+)\s+Question\s+\d+:\s*(.+?)$/m', $content, $starMatches);
        foreach ($starMatches[2] as $index => $questionText) {
            $stars = $starMatches[1][$index];
            $questions[] = [
                'number' => $index + 1,
                'text' => trim($questionText),
                'file' => basename($file),
                'type' => 'star-rated',
                'stars' => mb_strlen($stars)
            ];
        }
        
        // Pattern 3: Questions in sections (e.g., "### Q1: Question text" or "**Question**: Text")
        preg_match_all('/###\s+Q\d+:\s*(.+?)$/m', $content, $sectionMatches);
        foreach ($sectionMatches[1] as $index => $questionText) {
            $questions[] = [
                'number' => $index + 1,
                'text' => trim($questionText),
                'file' => basename($file),
                'type' => 'section'
            ];
        }
        
        // Pattern 4: Bold questions
        preg_match_all('/\*\*Question\*\*:\s*(.+?)$/m', $content, $boldMatches);
        foreach ($boldMatches[1] as $index => $questionText) {
            $questions[] = [
                'number' => $index + 1,
                'text' => trim($questionText),
                'file' => basename($file),
                'type' => 'bold'
            ];
        }
        
        // Remove duplicates based on text similarity
        $questions = $this->removeDuplicateQuestions($questions);
        
        return $questions;
    }
    
    private function removeDuplicateQuestions(array $questions): array
    {
        $unique = [];
        $seen = [];
        
        foreach ($questions as $question) {
            $normalized = strtolower(trim($question['text']));
            if (!in_array($normalized, $seen)) {
                $seen[] = $normalized;
                $unique[] = $question;
            }
        }
        
        return $unique;
    }
    
    private function detectDifficulty(string $questionText): string
    {
        // Check for star indicators
        if (strpos($questionText, '⭐⭐⭐⭐') !== false) return 'expert';
        if (strpos($questionText, '⭐⭐⭐') !== false) return 'advanced';
        if (strpos($questionText, '⭐⭐') !== false) return 'intermediate';
        if (strpos($questionText, '⭐') !== false) return 'basic';
        
        // Check for keywords
        $text = strtolower($questionText);
        
        if (preg_match('/\b(expert|system design|architecture|production-grade|scalability)\b/', $text)) {
            return 'expert';
        }
        if (preg_match('/\b(advanced|complex|optimize|implement|design)\b/', $text)) {
            return 'advanced';
        }
        if (preg_match('/\b(intermediate|explain|compare|demonstrate)\b/', $text)) {
            return 'intermediate';
        }
        if (preg_match('/\b(basic|what is|define|list|describe)\b/', $text)) {
            return 'basic';
        }
        
        return 'unknown';
    }
    
    private function isQuestionAnswered(array $question, array $answerFiles): bool
    {
        foreach ($answerFiles as $file) {
            $content = file_get_contents($file);
            
            // Look for the question text or number
            $questionText = preg_quote($question['text'], '/');
            $questionNumber = $question['number'];
            
            // Check if question text appears in answer file
            if (preg_match("/{$questionText}/i", $content)) {
                return true;
            }
            
            // Check for Q{number} pattern or #{number} pattern
            if (preg_match("/Q{$questionNumber}[:\s]|#{$questionNumber}[:\s]|^{$questionNumber}\./m", $content)) {
                return true;
            }
            
            // Check for "Answer" or "**Answer**:" after question
            if (preg_match("/{$questionText}.{0,500}\*\*Answer\*\*:/is", $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function detectDuplicates(): void
    {
        echo "Detecting duplicates...\n";
        
        $allQuestions = [];
        
        // Collect all questions with their category
        foreach ($this->categories as $categoryKey => $category) {
            foreach ($category['questions'] as $question) {
                $allQuestions[] = array_merge($question, ['category' => $categoryKey]);
            }
        }
        
        // Find duplicates
        $this->duplicates = [];
        for ($i = 0; $i < count($allQuestions); $i++) {
            for ($j = $i + 1; $j < count($allQuestions); $j++) {
                $similarity = $this->calculateSimilarity(
                    $allQuestions[$i]['text'],
                    $allQuestions[$j]['text']
                );
                
                if ($similarity > 80) {
                    $this->duplicates[] = [
                        'question1' => $allQuestions[$i],
                        'question2' => $allQuestions[$j],
                        'similarity' => $similarity
                    ];
                }
            }
        }
        
        echo "Found " . count($this->duplicates) . " potential duplicates\n";
    }
    
    private function calculateSimilarity(string $text1, string $text2): float
    {
        $text1 = strtolower(trim($text1));
        $text2 = strtolower(trim($text2));
        
        // Exact match
        if ($text1 === $text2) return 100.0;
        
        // Use similar_text function
        similar_text($text1, $text2, $percent);
        
        return round($percent, 2);
    }
    
    private function analyzeTopics(): void
    {
        echo "Analyzing topics...\n";
        
        $keywords = [
            'PHP' => ['php', 'php 8', 'php7', 'php8'],
            'Laravel' => ['laravel', 'eloquent', 'blade', 'artisan'],
            'Database' => ['database', 'mysql', 'postgresql', 'sql', 'query'],
            'Security' => ['security', 'authentication', 'authorization', 'owasp', 'pci dss'],
            'API' => ['api', 'rest', 'restful', 'endpoint', 'webhook'],
            'Payment' => ['payment', 'razorpay', 'stripe', 'gateway'],
            'WebSocket' => ['websocket', 'real-time', 'socket.io', 'pusher'],
            'Docker' => ['docker', 'container', 'containerization'],
            'Kubernetes' => ['kubernetes', 'k8s', 'kubectl', 'pod', 'deployment'],
            'AWS' => ['aws', 'lambda', 'ec2', 's3', 'cloud'],
            'Terraform' => ['terraform', 'iac', 'infrastructure as code'],
            'CI/CD' => ['ci/cd', 'jenkins', 'github actions', 'pipeline'],
            'React' => ['react', 'jsx', 'hooks', 'component'],
            'Next.js' => ['next.js', 'nextjs', 'next'],
            'TypeScript' => ['typescript', 'type safety'],
            'Redis' => ['redis', 'cache', 'caching'],
            'Testing' => ['test', 'testing', 'phpunit', 'jest'],
            'AI/ML' => ['ai', 'machine learning', 'llm', 'gpt', 'openai'],
            'Serverless' => ['serverless', 'lambda', 'function'],
            'DevOps' => ['devops', 'deployment', 'monitoring'],
        ];
        
        $this->topicFrequency = array_fill_keys(array_keys($keywords), 0);
        
        foreach ($this->categories as $category) {
            foreach ($category['questions'] as $question) {
                $text = strtolower($question['text']);
                
                foreach ($keywords as $topic => $patterns) {
                    foreach ($patterns as $pattern) {
                        if (stripos($text, $pattern) !== false) {
                            $this->topicFrequency[$topic]++;
                            break;
                        }
                    }
                }
            }
        }
        
        arsort($this->topicFrequency);
    }
    
    private function calculateStatistics(): void
    {
        echo "Calculating overall statistics...\n";
        
        $totalQuestions = 0;
        $totalAnswered = 0;
        $totalUnanswered = 0;
        $difficultyDistribution = [
            'basic' => 0,
            'intermediate' => 0,
            'advanced' => 0,
            'expert' => 0,
            'unknown' => 0
        ];
        
        foreach ($this->categories as $category) {
            $totalQuestions += $category['total_questions'];
            $totalAnswered += $category['answered'];
            $totalUnanswered += $category['unanswered'];
            
            foreach ($category['difficulty_distribution'] as $difficulty => $count) {
                $difficultyDistribution[$difficulty] += $count;
            }
        }
        
        $this->statistics = [
            'total_questions' => $totalQuestions,
            'total_answered' => $totalAnswered,
            'total_unanswered' => $totalUnanswered,
            'overall_coverage' => $totalQuestions > 0 
                ? round(($totalAnswered / $totalQuestions) * 100, 2)
                : 0,
            'difficulty_distribution' => $difficultyDistribution,
            'categories' => $this->categories,
            'duplicates' => $this->duplicates,
            'duplicate_count' => count($this->duplicates),
            'topic_frequency' => $this->topicFrequency,
            'category_count' => count($this->categories),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function formatCategoryName(string $category): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $category));
    }
    
    public function generateHtmlReport(): string
    {
        $stats = $this->statistics;
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Statistics Report - Interview Bank</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
            color: #333;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        header {
            background: white;
            padding: 3rem 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        header h1 {
            font-size: 3rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        header p {
            font-size: 1.2rem;
            color: #666;
        }
        
        .timestamp {
            font-size: 0.9rem;
            color: #999;
            margin-top: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section h2 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #667eea;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .progress-bar {
            height: 10px;
            background: #ecf0f1;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
        }
        
        .badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-basic { background: #d4edda; color: #155724; }
        .badge-intermediate { background: #fff3cd; color: #856404; }
        .badge-advanced { background: #f8d7da; color: #721c24; }
        .badge-expert { background: #d1ecf1; color: #0c5460; }
        .badge-unknown { background: #e2e3e5; color: #383d41; }
        
        .duplicate-item {
            background: #fff3cd;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #856404;
        }
        
        .duplicate-similarity {
            font-weight: 700;
            color: #856404;
        }
        
        .topic-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .topic-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .topic-count {
            font-weight: 700;
            color: #667eea;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Question Bank Statistics Report</h1>
            <p>Comprehensive analysis of the interview question bank</p>
            <p class="timestamp">Generated: {$stats['generated_at']}</p>
        </header>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value">{$stats['total_questions']}</div>
                <div class="stat-label">Total Questions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value">{$stats['total_answered']}</div>
                <div class="stat-label">Answered</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">❓</div>
                <div class="stat-value">{$stats['total_unanswered']}</div>
                <div class="stat-label">Unanswered</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-value">{$stats['overall_coverage']}%</div>
                <div class="stat-label">Coverage</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-value">{$stats['category_count']}</div>
                <div class="stat-label">Categories</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value">{$stats['duplicate_count']}</div>
                <div class="stat-label">Duplicates</div>
            </div>
        </div>
        
        <div class="chart-grid">
            <div class="section">
                <h2>Difficulty Distribution</h2>
                <div class="chart-container">
                    <canvas id="difficultyChart"></canvas>
                </div>
            </div>
            
            <div class="section">
                <h2>Coverage by Category</h2>
                <div class="chart-container">
                    <canvas id="coverageChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Category Statistics</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total</th>
                        <th>Answered</th>
                        <th>Unanswered</th>
                        <th>Coverage</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
HTML;

        foreach ($stats['categories'] as $category) {
            $coverage = $category['coverage_percentage'];
            $html .= <<<HTML
                    <tr>
                        <td><strong>{$category['name']}</strong></td>
                        <td>{$category['total_questions']}</td>
                        <td>{$category['answered']}</td>
                        <td>{$category['unanswered']}</td>
                        <td>{$coverage}%</td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {$coverage}%"></div>
                            </div>
                        </td>
                    </tr>
HTML;
        }

        $html .= <<<HTML
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>Difficulty Distribution by Category</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Basic</th>
                        <th>Intermediate</th>
                        <th>Advanced</th>
                        <th>Expert</th>
                        <th>Unknown</th>
                    </tr>
                </thead>
                <tbody>
HTML;

        foreach ($stats['categories'] as $category) {
            $diff = $category['difficulty_distribution'];
            $html .= <<<HTML
                    <tr>
                        <td><strong>{$category['name']}</strong></td>
                        <td><span class="badge badge-basic">{$diff['basic']}</span></td>
                        <td><span class="badge badge-intermediate">{$diff['intermediate']}</span></td>
                        <td><span class="badge badge-advanced">{$diff['advanced']}</span></td>
                        <td><span class="badge badge-expert">{$diff['expert']}</span></td>
                        <td><span class="badge badge-unknown">{$diff['unknown']}</span></td>
                    </tr>
HTML;
        }

        $html .= <<<HTML
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>Topic Frequency Analysis</h2>
            <div style="max-height: 600px; overflow-y: auto;">
HTML;

        foreach ($stats['topic_frequency'] as $topic => $count) {
            if ($count > 0) {
                $html .= <<<HTML
                <div class="topic-item">
                    <span class="topic-name">{$topic}</span>
                    <span class="topic-count">{$count}</span>
                </div>
HTML;
            }
        }

        $html .= <<<HTML
            </div>
        </div>
        
        <div class="section">
            <h2>Duplicate Detection Results</h2>
            <p style="margin-bottom: 1rem;">Found <strong>{$stats['duplicate_count']}</strong> potential duplicate questions (similarity > 80%)</p>
HTML;

        if ($stats['duplicate_count'] > 0) {
            $displayLimit = min(50, $stats['duplicate_count']);
            $html .= "<p style=\"margin-bottom: 1rem; color: #666;\">Showing first {$displayLimit} duplicates:</p>";
            
            for ($i = 0; $i < $displayLimit; $i++) {
                $dup = $stats['duplicates'][$i];
                $html .= <<<HTML
                <div class="duplicate-item">
                    <div class="duplicate-similarity">Similarity: {$dup['similarity']}%</div>
                    <p><strong>Question 1:</strong> {$dup['question1']['text']}</p>
                    <p style="margin-top: 0.5rem;"><em>Category: {$dup['question1']['category']}, File: {$dup['question1']['file']}</em></p>
                    <p style="margin-top: 0.5rem;"><strong>Question 2:</strong> {$dup['question2']['text']}</p>
                    <p style="margin-top: 0.5rem;"><em>Category: {$dup['question2']['category']}, File: {$dup['question2']['file']}</em></p>
                </div>
HTML;
            }
        } else {
            $html .= "<p>✅ No duplicate questions detected!</p>";
        }

        $html .= <<<HTML
        </div>
        
        <div class="section">
            <h2>Category Questions Breakdown</h2>
            <div class="chart-container" style="height: 500px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <script>
        // Difficulty Distribution Chart
        const diffCtx = document.getElementById('difficultyChart').getContext('2d');
        new Chart(diffCtx, {
            type: 'doughnut',
            data: {
                labels: ['Basic', 'Intermediate', 'Advanced', 'Expert', 'Unknown'],
                datasets: [{
                    data: [
                        {$stats['difficulty_distribution']['basic']},
                        {$stats['difficulty_distribution']['intermediate']},
                        {$stats['difficulty_distribution']['advanced']},
                        {$stats['difficulty_distribution']['expert']},
                        {$stats['difficulty_distribution']['unknown']}
                    ],
                    backgroundColor: [
                        '#d4edda',
                        '#fff3cd',
                        '#f8d7da',
                        '#d1ecf1',
                        '#e2e3e5'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 14 },
                            padding: 15
                        }
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
        
        // Coverage by Category Chart
        const coverageCtx = document.getElementById('coverageChart').getContext('2d');
        new Chart(coverageCtx, {
            type: 'bar',
            data: {
                labels: [
HTML;

        $categoryNames = array_map(function($cat) { return $cat['name']; }, $stats['categories']);
        $html .= "'" . implode("','", $categoryNames) . "'";

        $html .= <<<HTML
                ],
                datasets: [{
                    label: 'Coverage %',
                    data: [
HTML;

        $coverageData = array_map(function($cat) { return $cat['coverage_percentage']; }, $stats['categories']);
        $html .= implode(',', $coverageData);

        $html .= <<<HTML
                    ],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Category Questions Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: [
HTML;

        $html .= "'" . implode("','", $categoryNames) . "'";

        $html .= <<<HTML
                ],
                datasets: [
                    {
                        label: 'Answered',
                        data: [
HTML;

        $answeredData = array_map(function($cat) { return $cat['answered']; }, $stats['categories']);
        $html .= implode(',', $answeredData);

        $html .= <<<HTML
                        ],
                        backgroundColor: 'rgba(102, 126, 234, 0.8)'
                    },
                    {
                        label: 'Unanswered',
                        data: [
HTML;

        $unansweredData = array_map(function($cat) { return $cat['unanswered']; }, $stats['categories']);
        $html .= implode(',', $unansweredData);

        $html .= <<<HTML
                        ],
                        backgroundColor: 'rgba(231, 76, 60, 0.8)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    </script>
</body>
</html>
HTML;

        return $html;
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "=================================================\n";
    echo "Question Bank Statistics Analyzer\n";
    echo "=================================================\n\n";
    
    $analyzer = new QuestionStatisticsAnalyzer();
    $statistics = $analyzer->analyze();
    
    echo "\n=================================================\n";
    echo "Generating HTML Report...\n";
    echo "=================================================\n\n";
    
    $html = $analyzer->generateHtmlReport();
    $outputPath = dirname(__DIR__) . '/pages/question-statistics-report.html';
    
    file_put_contents($outputPath, $html);
    
    echo "✅ Report generated successfully!\n";
    echo "📄 Output: {$outputPath}\n\n";
    
    echo "Summary:\n";
    echo "- Total Questions: {$statistics['total_questions']}\n";
    echo "- Total Answered: {$statistics['total_answered']}\n";
    echo "- Total Unanswered: {$statistics['total_unanswered']}\n";
    echo "- Overall Coverage: {$statistics['overall_coverage']}%\n";
    echo "- Categories: {$statistics['category_count']}\n";
    echo "- Duplicates Found: {$statistics['duplicate_count']}\n";
    
    echo "\n=================================================\n";
    echo "Analysis Complete!\n";
    echo "=================================================\n";
}
