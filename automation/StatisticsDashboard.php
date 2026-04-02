<?php

namespace InterviewBank\Automation;

class StatisticsDashboard
{
    public function generateDashboard(array $metadataCollection): string
    {
        $summary = $this->calculateSummary($metadataCollection);
        
        $dashboard = "# Interview Bank Statistics Dashboard\n\n";
        $dashboard .= "**Generated:** " . date('Y-m-d H:i:s') . "\n\n";
        $dashboard .= "---\n\n";
        
        $dashboard .= $this->generateOverview($summary);
        $dashboard .= $this->generateDifficultyDistribution($summary);
        $dashboard .= $this->generateDomainBreakdown($summary);
        $dashboard .= $this->generateTechnologyMatrix($summary);
        $dashboard .= $this->generateCodeStatistics($summary);
        $dashboard .= $this->generateContentMetrics($summary);
        
        return $dashboard;
    }

    private function calculateSummary(array $metadataCollection): array
    {
        $summary = [
            'total_files' => count($metadataCollection),
            'total_questions' => 0,
            'total_code_blocks' => 0,
            'total_mermaid_diagrams' => 0,
            'total_word_count' => 0,
            'total_line_count' => 0,
            'by_difficulty' => ['basic' => 0, 'intermediate' => 0, 'advanced' => 0, 'expert' => 0],
            'by_domain' => [],
            'by_technology' => [],
            'by_language' => [],
            'by_topic' => [],
            'files_by_domain_difficulty' => []
        ];

        foreach ($metadataCollection as $metadata) {
            $summary['total_questions'] += count($metadata['questions']);
            $summary['total_code_blocks'] += count($metadata['code_examples']);
            $summary['total_mermaid_diagrams'] += count($metadata['mermaid_diagrams']);
            $summary['total_word_count'] += $metadata['word_count'];
            $summary['total_line_count'] += $metadata['line_count'];

            $difficulty = $metadata['difficulty'];
            $summary['by_difficulty'][$difficulty]++;

            $domain = $metadata['domain'];
            if (!isset($summary['by_domain'][$domain])) {
                $summary['by_domain'][$domain] = [
                    'files' => 0,
                    'questions' => 0,
                    'code_blocks' => 0,
                    'mermaid_diagrams' => 0,
                    'word_count' => 0,
                    'by_difficulty' => ['basic' => 0, 'intermediate' => 0, 'advanced' => 0, 'expert' => 0]
                ];
            }
            $summary['by_domain'][$domain]['files']++;
            $summary['by_domain'][$domain]['questions'] += count($metadata['questions']);
            $summary['by_domain'][$domain]['code_blocks'] += count($metadata['code_examples']);
            $summary['by_domain'][$domain]['mermaid_diagrams'] += count($metadata['mermaid_diagrams']);
            $summary['by_domain'][$domain]['word_count'] += $metadata['word_count'];
            $summary['by_domain'][$domain]['by_difficulty'][$difficulty]++;

            foreach ($metadata['technologies'] as $tech) {
                $techName = $tech['name'];
                if (!isset($summary['by_technology'][$techName])) {
                    $summary['by_technology'][$techName] = [
                        'files' => 0,
                        'mentions' => 0,
                        'domains' => []
                    ];
                }
                $summary['by_technology'][$techName]['files']++;
                $summary['by_technology'][$techName]['mentions'] += $tech['mentions'];
                $summary['by_technology'][$techName]['domains'][$domain] = 
                    ($summary['by_technology'][$techName]['domains'][$domain] ?? 0) + 1;
            }

            foreach ($metadata['code_examples'] as $example) {
                $lang = $example['language'];
                if (!isset($summary['by_language'][$lang])) {
                    $summary['by_language'][$lang] = [
                        'count' => 0,
                        'total_lines' => 0
                    ];
                }
                $summary['by_language'][$lang]['count']++;
                $summary['by_language'][$lang]['total_lines'] += $example['lines'];
            }

            foreach ($metadata['topics'] as $topic) {
                if (!isset($summary['by_topic'][$topic])) {
                    $summary['by_topic'][$topic] = 0;
                }
                $summary['by_topic'][$topic]++;
            }
        }

        uasort($summary['by_technology'], fn($a, $b) => $b['mentions'] <=> $a['mentions']);
        uasort($summary['by_language'], fn($a, $b) => $b['count'] <=> $a['count']);
        arsort($summary['by_topic']);

        return $summary;
    }

    private function generateOverview(array $summary): string
    {
        $output = "## 📊 Overview\n\n";
        $output .= "| Metric | Value |\n";
        $output .= "|--------|-------|\n";
        $output .= "| Total Files | " . number_format($summary['total_files']) . " |\n";
        $output .= "| Total Questions | " . number_format($summary['total_questions']) . " |\n";
        $output .= "| Total Code Blocks | " . number_format($summary['total_code_blocks']) . " |\n";
        $output .= "| Total Mermaid Diagrams | " . number_format($summary['total_mermaid_diagrams']) . " |\n";
        $output .= "| Total Words | " . number_format($summary['total_word_count']) . " |\n";
        $output .= "| Total Lines | " . number_format($summary['total_line_count']) . " |\n";
        $output .= "| Avg Questions/File | " . number_format($summary['total_questions'] / max(1, $summary['total_files']), 1) . " |\n";
        $output .= "| Avg Code Blocks/File | " . number_format($summary['total_code_blocks'] / max(1, $summary['total_files']), 1) . " |\n";
        $output .= "\n";

        return $output;
    }

    private function generateDifficultyDistribution(array $summary): string
    {
        $output = "## 🎯 Difficulty Distribution\n\n";
        $output .= "| Difficulty | Files | Percentage |\n";
        $output .= "|------------|-------|------------|\n";
        
        $total = $summary['total_files'];
        foreach ($summary['by_difficulty'] as $level => $count) {
            $percentage = $total > 0 ? ($count / $total * 100) : 0;
            $bar = $this->generateProgressBar($percentage);
            $icon = $this->getDifficultyIcon($level);
            $output .= sprintf("| %s %s | %d | %.1f%% %s |\n", 
                $icon, ucfirst($level), $count, $percentage, $bar);
        }
        $output .= "\n";

        $output .= "### Difficulty Level Legend\n\n";
        $output .= "- ⭐ **Basic**: Fundamental concepts, 15-30 minutes\n";
        $output .= "- ⭐⭐ **Intermediate**: Practical application, 30-45 minutes\n";
        $output .= "- ⭐⭐⭐ **Advanced**: Complex scenarios, 45-60 minutes\n";
        $output .= "- ⭐⭐⭐⭐ **Expert**: System design, 60-90 minutes\n\n";

        return $output;
    }

    private function generateDomainBreakdown(array $summary): string
    {
        $output = "## 📚 Domain Breakdown\n\n";
        $output .= "| Domain | Files | Questions | Code | Diagrams | Words |\n";
        $output .= "|--------|-------|-----------|------|----------|-------|\n";

        foreach ($summary['by_domain'] as $domain => $stats) {
            $domainName = $this->formatDomainName($domain);
            $output .= sprintf("| %s | %d | %d | %d | %d | %s |\n",
                $domainName,
                $stats['files'],
                $stats['questions'],
                $stats['code_blocks'],
                $stats['mermaid_diagrams'],
                number_format($stats['word_count'])
            );
        }
        $output .= "\n";

        $output .= "### Difficulty Distribution by Domain\n\n";
        foreach ($summary['by_domain'] as $domain => $stats) {
            $domainName = $this->formatDomainName($domain);
            $output .= "**{$domainName}**\n\n";
            $output .= "| Difficulty | Count | Percentage |\n";
            $output .= "|------------|-------|------------|\n";
            
            $total = $stats['files'];
            foreach ($stats['by_difficulty'] as $level => $count) {
                if ($count > 0) {
                    $percentage = ($count / $total * 100);
                    $bar = $this->generateProgressBar($percentage);
                    $output .= sprintf("| %s | %d | %.1f%% %s |\n",
                        ucfirst($level), $count, $percentage, $bar);
                }
            }
            $output .= "\n";
        }

        return $output;
    }

    private function generateTechnologyMatrix(array $summary): string
    {
        $output = "## 💻 Technology Coverage\n\n";
        $output .= "Top 20 technologies by mention frequency:\n\n";
        $output .= "| Technology | Files | Mentions | Domains |\n";
        $output .= "|------------|-------|----------|----------|\n";

        $count = 0;
        foreach ($summary['by_technology'] as $tech => $stats) {
            if ($count++ >= 20) break;
            
            $domainList = implode(', ', array_keys($stats['domains']));
            $output .= sprintf("| %s | %d | %d | %s |\n",
                $tech,
                $stats['files'],
                $stats['mentions'],
                $domainList
            );
        }
        $output .= "\n";

        return $output;
    }

    private function generateCodeStatistics(array $summary): string
    {
        $output = "## 💾 Code Statistics\n\n";
        $output .= "### Programming Languages\n\n";
        $output .= "| Language | Code Blocks | Total Lines | Avg Lines/Block |\n";
        $output .= "|----------|-------------|-------------|------------------|\n";

        foreach ($summary['by_language'] as $lang => $stats) {
            $avgLines = $stats['count'] > 0 ? ($stats['total_lines'] / $stats['count']) : 0;
            $output .= sprintf("| %s | %d | %d | %.1f |\n",
                ucfirst($lang),
                $stats['count'],
                $stats['total_lines'],
                $avgLines
            );
        }
        $output .= "\n";

        return $output;
    }

    private function generateContentMetrics(array $summary): string
    {
        $output = "## 📈 Content Metrics\n\n";
        
        $output .= "### Top 15 Topics\n\n";
        $output .= "| Topic | Occurrences |\n";
        $output .= "|-------|-------------|\n";
        
        $count = 0;
        foreach ($summary['by_topic'] as $topic => $occurrences) {
            if ($count++ >= 15) break;
            $output .= sprintf("| %s | %d |\n", $topic, $occurrences);
        }
        $output .= "\n";

        return $output;
    }

    private function formatDomainName(string $domain): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $domain));
    }

    private function getDifficultyIcon(string $level): string
    {
        return match($level) {
            'basic' => '⭐',
            'intermediate' => '⭐⭐',
            'advanced' => '⭐⭐⭐',
            'expert' => '⭐⭐⭐⭐',
            default => '❓'
        };
    }

    private function generateProgressBar(float $percentage, int $width = 10): string
    {
        $filled = (int)round($percentage / 10);
        $filled = min($filled, $width);
        return str_repeat('█', $filled) . str_repeat('░', $width - $filled);
    }

    public function generateJsonReport(array $metadataCollection): string
    {
        $summary = $this->calculateSummary($metadataCollection);
        
        $report = [
            'generated_at' => date('c'),
            'summary' => [
                'total_files' => $summary['total_files'],
                'total_questions' => $summary['total_questions'],
                'total_code_blocks' => $summary['total_code_blocks'],
                'total_mermaid_diagrams' => $summary['total_mermaid_diagrams'],
                'total_word_count' => $summary['total_word_count']
            ],
            'difficulty_distribution' => $summary['by_difficulty'],
            'domain_breakdown' => $summary['by_domain'],
            'technology_coverage' => $summary['by_technology'],
            'programming_languages' => $summary['by_language'],
            'topics' => $summary['by_topic']
        ];

        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function generateHtmlDashboard(array $metadataCollection): string
    {
        $summary = $this->calculateSummary($metadataCollection);
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Bank Statistics Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
               background: #f5f7fa; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #2c3e50; margin-bottom: 10px; }
        .timestamp { color: #7f8c8d; margin-bottom: 30px; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
                 gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card h3 { color: #34495e; font-size: 14px; margin-bottom: 10px; }
        .card .value { font-size: 32px; font-weight: bold; color: #3498db; }
        .card .label { color: #7f8c8d; font-size: 12px; margin-top: 5px; }
        .section { background: white; padding: 25px; border-radius: 8px; 
                   box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .section h2 { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #3498db; 
                      padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; color: #2c3e50; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .progress-bar { height: 8px; background: #ecf0f1; border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #3498db, #2ecc71); }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; 
                 font-size: 11px; font-weight: 600; }
        .badge-basic { background: #d4edda; color: #155724; }
        .badge-intermediate { background: #fff3cd; color: #856404; }
        .badge-advanced { background: #f8d7da; color: #721c24; }
        .badge-expert { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Interview Bank Statistics Dashboard</h1>
        <div class="timestamp">Generated: {$this->formatDateTime()}</div>
        
        <div class="cards">
HTML;

        $html .= $this->generateMetricCard('Total Files', $summary['total_files'], '📄');
        $html .= $this->generateMetricCard('Total Questions', $summary['total_questions'], '❓');
        $html .= $this->generateMetricCard('Code Blocks', $summary['total_code_blocks'], '💻');
        $html .= $this->generateMetricCard('Diagrams', $summary['total_mermaid_diagrams'], '📊');

        $html .= '</div>';

        $html .= $this->generateDifficultySection($summary);
        $html .= $this->generateDomainSection($summary);
        $html .= $this->generateTechnologySection($summary);

        $html .= <<<HTML
    </div>
</body>
</html>
HTML;

        return $html;
    }

    private function generateMetricCard(string $label, int $value, string $icon): string
    {
        return <<<HTML
            <div class="card">
                <h3>{$icon} {$label}</h3>
                <div class="value">{$this->formatNumber($value)}</div>
            </div>
HTML;
    }

    private function generateDifficultySection(array $summary): string
    {
        $html = '<div class="section"><h2>🎯 Difficulty Distribution</h2><table>';
        $html .= '<tr><th>Level</th><th>Files</th><th>Percentage</th><th>Distribution</th></tr>';
        
        $total = $summary['total_files'];
        foreach ($summary['by_difficulty'] as $level => $count) {
            $percentage = $total > 0 ? ($count / $total * 100) : 0;
            $badgeClass = "badge-{$level}";
            $html .= "<tr>";
            $html .= "<td><span class='badge {$badgeClass}'>" . ucfirst($level) . "</span></td>";
            $html .= "<td>{$count}</td>";
            $html .= "<td>" . number_format($percentage, 1) . "%</td>";
            $html .= "<td><div class='progress-bar'><div class='progress-fill' style='width: {$percentage}%'></div></div></td>";
            $html .= "</tr>";
        }
        
        $html .= '</table></div>';
        return $html;
    }

    private function generateDomainSection(array $summary): string
    {
        $html = '<div class="section"><h2>📚 Domain Breakdown</h2><table>';
        $html .= '<tr><th>Domain</th><th>Files</th><th>Questions</th><th>Code</th><th>Diagrams</th></tr>';
        
        foreach ($summary['by_domain'] as $domain => $stats) {
            $html .= "<tr>";
            $html .= "<td><strong>" . $this->formatDomainName($domain) . "</strong></td>";
            $html .= "<td>{$stats['files']}</td>";
            $html .= "<td>{$stats['questions']}</td>";
            $html .= "<td>{$stats['code_blocks']}</td>";
            $html .= "<td>{$stats['mermaid_diagrams']}</td>";
            $html .= "</tr>";
        }
        
        $html .= '</table></div>';
        return $html;
    }

    private function generateTechnologySection(array $summary): string
    {
        $html = '<div class="section"><h2>💻 Technology Coverage (Top 15)</h2><table>';
        $html .= '<tr><th>Technology</th><th>Files</th><th>Mentions</th></tr>';
        
        $count = 0;
        foreach ($summary['by_technology'] as $tech => $stats) {
            if ($count++ >= 15) break;
            $html .= "<tr>";
            $html .= "<td><strong>{$tech}</strong></td>";
            $html .= "<td>{$stats['files']}</td>";
            $html .= "<td>{$stats['mentions']}</td>";
            $html .= "</tr>";
        }
        
        $html .= '</table></div>';
        return $html;
    }

    private function formatNumber(int $number): string
    {
        return number_format($number);
    }

    private function formatDateTime(): string
    {
        return date('F j, Y \a\t g:i A');
    }
}
